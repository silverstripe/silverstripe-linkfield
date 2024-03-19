<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tasks;

use LogicException;
use RuntimeException;
use SilverStripe\Assets\Shortcodes\FileLink as WYSIWYGFileLink;
use SilverStripe\CMS\Model\SiteTreeLink as WYSIWYGSiteTreeLink;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Dev\Deprecation;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Versioned\ChangeSet;
use SilverStripe\Versioned\ChangeSetItem;
use SilverStripe\Versioned\Versioned;

/**
 * @deprecated 4.0.0 Will be removed without equivalent functionality.
 */
class LinkFieldMigrationTask extends BuildTask
{
    private static $segment = 'linkfield-tov4-migration-task';

    protected $title = 'Linkfield v2/3 to v4 Migration Task';

    protected $description = 'Migrate from silverstripe/linkfield v2 or v3 to v4';

    /**
     * Enable via YAML configuration if you need to run this task
     */
    private static ?bool $is_enabled = false;

    /**
     * Classes which should be skipped when finding owners of links.
     * These classes and all of their subclasses will be skipped.
     */
    private static array $classes_that_are_not_link_owners = [
        // Skip models that are used for internal tracking purposes and cannot own links
        ChangeSet::class,
        ChangeSetItem::class,
        WYSIWYGFileLink::class,
        WYSIWYGSiteTreeLink::class,
    ];

    /**
     * List any has_many relations that should be migrated.
     *
     * Keys are the FQCN for the class where the has_many is declared.
     * Values are an associative array of the Link class that held the has_one
     * and the name of the old has_one.
     *
     * Example:
     * <code>
     * // SiteConfig had two has_many relationships,
     * // one to Link.MyHasOne and another to ExternalLink.DifferentHasOne.
     * SiteConfig::class => [
     *   'LinksListOne' => [
     *     'linkClass' => Link::class,
     *     'hasOne' => 'MyHasOne',
     *   ]
     *   'LinksListTwo' => [
     *     'linkClass' => ExternalLink::class,
     *     'hasOne' => 'DifferentHasOne',
     *   ]
     * ]
     * </code>
     */
    private static array $has_many_links_data = [];

    /**
     * in-memory cache of Link relational data so we don't keep slamming the filesystem cache
     * when checking these relations in CLI
     */
    private array $linkRelationData = [];

    public function __construct()
    {
        // Use withNoReplacement() because otherwise even viewing the dev/tasks list will trigger this warning.
        Deprecation::withNoReplacement(
            fn () => Deprecation::notice('4.0.0', 'Will be removed without equivalent functionality.', Deprecation::SCOPE_CLASS)
        );
        parent::__construct();
    }

    public function run($request): void
    {
        $db = DB::get_conn();
        $baseTable = DataObject::getSchema()->baseDataTable(Link::class);

        // If we don't need to migrate, exit early.
        if (!$this->getNeedsMigration($baseTable)) {
            $this->print('Cannot perform migration.');
            return;
        }

        if (!$db->supportsTransactions()) {
            $this->print('Database transactions are not supported for this database. Errors may result in a partially-migrated state.');
        }

        $db->withTransaction([$this, 'performMigration'], [$this, 'failedTransaction']);

        if ($request->getVar('skipBrokenLinks')) {
            $this->print('Skipping broken link check as requested.');
        } else {
            $this->checkForBrokenLinks();
        }

        $this->print('Done.');
    }

    /**
     * Used in a callback if there is an error with the migration that causes a rolled back DB transaction
     */
    public function failedTransaction()
    {
        if (DB::get_conn()->supportsTransactions()) {
            $this->print('There was an error with the migration. Rolling back.');
        }
    }

    /**
     * Perform the actual data migration and publish links as appropriate
     */
    public function performMigration()
    {
        // Migrate data
        $this->migrateTitleColumn();
        $this->migrateHasManyRelations();
        $this->setOwnerForHasOneLinks();

        $this->print('-----------------');
        $this->print('Bulk data migration complete. All links should be correct (but unpublished) at this stage.');
        $this->print('-----------------');

        $this->publishLinks();

        $this->print('-----------------');
        $this->print('Migration completed successfully.');
        $this->print('-----------------');
    }

    /**
     * Check if we actually need to migrate anything, and if not give clear output as to why not.
     */
    private function getNeedsMigration(string $baseTable): bool
    {
        $needsMigration = false;
        $needColumns = ['LinkText', 'Title'];
        $baseDbColumns = array_keys(DB::field_list($baseTable));
        $baseNeededColumns = array_intersect($needColumns, $baseDbColumns);

        // If we have all of the requisite columns, we can proceed.
        if ($baseNeededColumns === $needColumns) {
            $needsMigration = true;
            // Lets developers swap the true to a false if they know something we don't about their setup.
            $this->extend('updateNeedsMigration', $needsMigration);
            if (!$needsMigration) {
                $this->print('Skipping migration due to project-level customisation.');
            }
            return $needsMigration;
        }

        // Lets developers swap the false to a true if they know something we don't about their setup.
        $this->extend('updateNeedsMigration', $needsMigration);
        if ($needsMigration) {
            $this->print('Not skipping migration due to project-level customisation.');
        }

        // If we're missing anything, give clear output about what the situation is.
        $missingColumns = array_diff($needColumns, $baseNeededColumns);
        if (count($missingColumns) > 1) {
            $this->print('Missing multiple columns in the database. This usually happens in new installations before dev/build.');
            return $needsMigration;
        }
        $missingColumnKey = array_key_first($missingColumns);
        switch ($missingColumns[$missingColumnKey]) {
            case 'Title':
                $this->print('Missing "Title" column in database. This usually means you have already run this task or do not need to migrate.');
                break;
            case 'LinkText':
                $this->print('Missing "LinkText" column in database. This usually means you need to upgrade your silverstripe/linkfield dependency and run dev/build.');
                break;
            default:
                // This should never happen, but better to throw an exception here than to assume nothing went wrong.
                throw new LogicException("Got unexpected missing column '{$missingColumns[$missingColumnKey]}'.");
        }
        return $needsMigration;
    }

    /**
     * Migrate the old Title column to the new LinkText column
     */
    private function migrateTitleColumn(): void
    {
        $this->extend('beforeMigrateTitleColumn');

        // Migrate base Link table
        $baseTable = DataObject::getSchema()->baseDataTable(Link::class);
        $this->print("Migrating data in '$baseTable' table.");
        $this->migrateColumn($baseTable, 'Title', 'LinkText');

        // Migrate versioned Link tables
        $needColumns = ['LinkText', 'Title'];
        if (Link::has_extension(Versioned::class)) {
            // Migrate `_Versions` and `_Live` tables
            foreach (["{$baseTable}_Versions", "{$baseTable}_Live"] as $versionedTable) {
                $versionedDbColumns = array_keys(DB::field_list($versionedTable));
                // If we don't have both columns in the table, skip migrating this table
                if (array_intersect($needColumns, $versionedDbColumns) !== $needColumns) {
                    $this->print("Nothing to migrate in '$versionedTable' table.");
                    continue;
                }
                $this->print("Migrating data in '$versionedTable' table.");
                $this->migrateColumn($versionedTable, 'Title', 'LinkText');
            }
        }
        $this->extend('afterMigrateTitleColumn');
    }

    /**
     * Set the $migrateFromColumn value to the value of the $migrateToColumn column, but don't replace values that already exist.
     */
    private function migrateColumn(string $table, string $migrateFromColumn, string $migrateToColumn, bool $columnIsNumeric = false): void
    {
        // Give developers a chance to skip migrating this column
        $shouldMigrateColumn = true;
        $this->extend('updateShouldMigrateColumn', $table, $migrateFromColumn, $shouldMigrateColumn);
        if (!$shouldMigrateColumn) {
            $this->print("Skipping migration of '{$table}.{$migrateFromColumn}' column due to project-level customisation.");
            return;
        }
        $db = DB::get_conn();
        $fromDbColumn = $db->escapeIdentifier($table . '.' . $migrateFromColumn);
        $toDbColumn = $db->escapeIdentifier($table . '.' . $migrateToColumn);
        // Migrate the data
        $nullCheck = $db->nullCheckClause($toDbColumn, true);
        SQLUpdate::create(
            $db->escapeIdentifier($table),
            [$toDbColumn => [$fromDbColumn => []]],
            // Only set if there's no value in that column already
            [$columnIsNumeric ? "$toDbColumn = 0 OR $nullCheck" : $nullCheck]
        )->execute();

        // Give developers a chance to skip dropping from column
        $shouldDropColumn = true;
        $this->extend('updateShouldDropColumn', $table, $migrateFromColumn, $shouldDropColumn);
        if (!$shouldDropColumn) {
            $this->print("Skipping dropping '{$table}.{$migrateFromColumn}' column due to project-level customisation.");
            return;
        }
        // Remove the column from the db
        $this->print("Dropping '{$table}.{$migrateFromColumn}' column.");
        $db->query("ALTER TABLE \"$table\" DROP COLUMN \"{$migrateFromColumn}\"");
    }

    private function migrateHasManyRelations(): void
    {
        $this->extend('beforeMigrateHasManyRelations');
        $linksList = static::config()->get('has_many_links_data');

        // Exit early if there's nothing to migrate
        if (empty($linksList)) {
            $this->print('No has_many relations to migrate.');
            $this->extend('afterMigrateHasManyRelations');
            return;
        }

        $this->print('Migrating has_many relations.');
        $schema = DataObject::getSchema();
        $db = DB::get_conn();
        foreach ($linksList as $ownerClass => $relationData) {
            foreach ($relationData as $hasManyRelation => $spec) {
                $linkClass = $spec['linkClass'];
                $hasOneRelation = $spec['hasOne'];
                // Skip if the has_one relation still exists
                if (array_key_exists($hasOneRelation, $this->getLinkRelationData($linkClass, 'has_one'))) {
                    throw new RuntimeException("has_one relation '{$linkClass}.{$hasOneRelation} still exists. Cannot migrate has_many relation '{$ownerClass}.{$hasManyRelation}'.");
                };
                // Check if HasOneID column is in the base Link table
                if (!array_key_exists("{$hasOneRelation}ID", DB::field_list(DataObject::getSchema()->baseDataTable(Link::class)))) {
                    // This is an unusual situation, and is difficult to do generically since SQLUpdate in framework
                    // can't use joins. We'll leave this scenario up to the developer to handle.
                    $this->extend('migrateHasOneForLinkSubclass', $linkClass, $ownerClass, $hasOneRelation, $hasManyRelation);
                    continue;
                }
                $linkTable = $schema->tableForField($linkClass, 'OwnerID');
                $tables = [$linkTable];
                // Include versioned tables if link is versioned
                if (Link::has_extension(Versioned::class)) {
                    $tables[] = "{$linkTable}_Versions";
                    $tables[] = "{$linkTable}_Live";
                }
                // Migrate old has_one on link to the Owner relation.
                foreach ($tables as $table) {
                    // Only try migration if we have both columns (e.g. versioned tables may not have the old has_one column)
                    $needColumns = ["{$hasOneRelation}ID", 'OwnerID'];
                    $columnsInTable = array_keys(DB::field_list($table));
                    if (array_intersect($needColumns, $columnsInTable) !== $needColumns) {
                        continue;
                    }
                    $this->migrateColumn($table, "{$hasOneRelation}ID", 'OwnerID', true);
                    // Only set Class and Relation where the ID got migrated
                    $ownerIdColumn = $db->escapeIdentifier($table . '.OwnerID');
                    $nullCheck = $db->nullCheckClause($ownerIdColumn, false);
                    $whereClause = [
                        "$ownerIdColumn != 0 AND $nullCheck",
                        $db->nullCheckClause($db->escapeIdentifier($table . '.OwnerRelation'), true),
                    ];
                    $wasPolyMorphic = array_key_exists("{$hasOneRelation}Class", DB::field_list($table));
                    if ($wasPolyMorphic) {
                        // For polymorphic relations, don't set the class/relation columns for records belonging
                        // to a different class hierarchy.
                        $validClasses = ClassInfo::subclassesFor($ownerClass, true);
                        $placeholders = DB::placeholders($validClasses);
                        $whereClause[] = [$db->escapeIdentifier("{$table}.{$hasOneRelation}Class") . " IN ($placeholders)" => $validClasses];
                    }
                    // Make sure we get the actual class name, not just the base class name.
                    $subSelect = SQLSelect::create(
                        $schema->sqlColumnForField($ownerClass, 'ClassName'),
                        $schema->baseDataTable($ownerClass),
                        'ID = ' . $ownerIdColumn
                    )->sql();
                    SQLUpdate::create(
                        $db->escapeIdentifier($table),
                        [
                            $db->escapeIdentifier($table . '.OwnerClass') => ["({$subSelect})" => []],
                            $db->escapeIdentifier($table . '.OwnerRelation') => $hasManyRelation,
                        ],
                        $whereClause
                    )->execute();
                    if ($wasPolyMorphic) {
                        $this->print("Dropping '{$table}.{$hasOneRelation}Class' column.");
                        $db->query("ALTER TABLE \"$table\" DROP COLUMN \"{$hasOneRelation}Class\"");
                    }
                }
            }
        }
        $this->extend('afterMigrateHasManyRelations');
    }

    /**
     * Find all `has_one` relations to link and set the corresponding `Owner` relation
     */
    private function setOwnerForHasOneLinks(): void
    {
        $this->extend('beforeSetOwnerForHasOneLinks');
        $this->print('Setting owners for has_one relations.');
        $allDataObjectModels = ClassInfo::subclassesFor(DataObject::class, false);
        $allLinkModels = ClassInfo::subclassesFor(Link::class, true);
        foreach ($allDataObjectModels as $modelClass) {
            if ($this->shouldSkipClassForOwnerCheck($modelClass)) {
                continue;
            }
            $hasOnes = Config::forClass($modelClass)->uninherited('has_one') ?? [];
            foreach ($hasOnes as $hasOneName => $spec) {
                // Get the class of the has_one
                $hasOneClass = $spec['class'] ?? null;
                if (!is_array($spec)) {
                    $hasOneClass = $spec;
                    $spec = ['class' => $hasOneClass];
                }

                // Skip malformed has_one relations
                if ($hasOneClass === null) {
                    continue;
                }

                // Polymorphic has_one needs some extra handling
                if ($hasOneClass === DataObject::class) {
                    if ($this->hasReciprocalRelation($allLinkModels, $hasOneName, $modelClass)) {
                        continue;
                    }
                    $this->updateOwnerForRelation(Link::class, $hasOneName, $modelClass, $spec);
                    continue;
                }

                // Skip if the has_one isn't for Link, or points at a belongs_to or has_many on Link
                if (!is_a($hasOneClass, Link::class, true)) {
                    continue;
                }
                if ($this->hasReciprocalRelation([$hasOneClass], $hasOneName, $modelClass)) {
                    continue;
                }

                // Update Owner for the relevant links to point at this relation
                $this->updateOwnerForRelation($hasOneClass, $hasOneName, $modelClass);
            }
        }
        $this->extend('afterSetOwnerForHasOneLinks');
    }

    private function shouldSkipClassForOwnerCheck(string $modelClass): bool
    {
        // This is a workaround for tests, since ClassInfo will get info about all TestOnly classes,
        // even if they're not in your test class's "extra_dataobjects" list.
        // Some classes don't have tables and don't NEED tables - but those classes also
        // won't declare has_one relations, so it's okay to skip those too.
        if (!ClassInfo::hasTable(DataObject::getSchema()->tableName($modelClass))) {
            return true;
        }
        // Skip class hierarchies that we explicitly said we want to skip
        $classHierarchiesToSkip = static::config()->get('classes_that_are_not_link_owners') ?? [];
        foreach ($classHierarchiesToSkip as $skipClass) {
            if (is_a($modelClass, $skipClass, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Store relation data in memory so we're not hitting config over and over again unnecessarily.
     * The task is likely run in CLI which relies on filesystem cache for config.
     */
    private function getLinkRelationData(string $linkClass, string $configName): array
    {
        if (!isset($this->linkRelationData[$linkClass][$configName])) {
            $config = Config::forClass($linkClass);
            $this->linkRelationData[$linkClass][$configName] = $config->uninherited($configName) ?? [];
        }
        return $this->linkRelationData[$linkClass][$configName];
    }

    private function hasReciprocalRelation(array $linkClasses, string $hasOneName, string $foreignClass): bool
    {
        foreach ($linkClasses as $linkClass) {
            $relationData = array_merge(
                $this->getLinkRelationData($linkClass, 'belongs_to'),
                $this->getLinkRelationData($linkClass, 'has_many'),
            );
            // Check if the given link class has a belongs_to or has_many pointing at the has_one relation
            // we're asking about
            foreach ($relationData as $relationName => $value) {
                $parsedRelation = $this->parseRelationData($value);

                if ($foreignClass !== $parsedRelation['class']) {
                    continue;
                }

                // If we can't tell what relation the belongs_to or has_many points at,
                // assume it's for the relation we're asking about
                if ($parsedRelation['reciprocalRelation'] === null) {
                    // Printing so developers can double check after the task is run.
                    // They can manually set the owner if it turns out our assumption was wrong.
                    // Not adding an extension point here because developers should use dot notation for the relation instead
                    // of working around their ambiguous relation declaration.
                    $this->print("Ambiguous relation '{$linkClass}.{$relationName}' found - assuming it points at '{$foreignClass}.{$hasOneName}'");
                    return true;
                }

                if ($hasOneName !== $parsedRelation['reciprocalRelation']) {
                    continue;
                }

                // If we get here, then the relation points back at the has_one we're
                // checking against.
                return true;
            }
        }
        return false;
    }

    /**
     * Parses a belongs_to or has_many relation class to separate the class from
     * the reciprocal relation name.
     *
     * Modified from RelationValidationService in framework.
     */
    private function parseRelationData(string $relationData): array
    {
        if (mb_strpos($relationData ?? '', '.') === false) {
            return [
                'class' => $relationData,
                'reciprocalRelation' => null,
            ];
        }

        $segments = explode('.', $relationData ?? '');

        // Theoretically this is the same as the mb_strpos check above,
        // but both checks are in RelationValidationService so I'm leaving
        // this here in case there's some edge case it's covering.
        if (count($segments) !== 2) {
            return [
                'class' => $relationData,
                'reciprocalRelation' => null,
            ];
        }

        $class = array_shift($segments);
        $relation = array_shift($segments);
        return [
            'class' => $class,
            'reciprocalRelation' => $relation,
        ];
    }

    /**
     * Bulk update the owner for links stored in a has_one relation
     */
    private function updateOwnerForRelation(string $linkClass, string $hasOneName, string $foreignClass, array $polymorphicSpec = []): void
    {
        $db = DB::get_conn();
        $schema = DataObject::getSchema();
        $isPolymorphic = !empty($polymorphicSpec);

        $ownerIdColumn = $schema->sqlColumnForField($linkClass, 'OwnerID');
        $ownerClassColumn = $schema->sqlColumnForField($linkClass, 'OwnerClass');
        $ownerRelationColumn = $schema->sqlColumnForField($linkClass, 'OwnerRelation');
        $linkIdColumn = $schema->sqlColumnForField($linkClass, 'ID');
        $relationIdColumn = $schema->sqlColumnForField($foreignClass, "{$hasOneName}ID");

        $nullCheck = $db->nullCheckClause($ownerIdColumn, true);
        $baseTable = $schema->tableForField($linkClass, 'OwnerID');
        $update = SQLUpdate::create(
            $db->escapeIdentifier($baseTable),
            [
                $ownerIdColumn => [$schema->sqlColumnForField($foreignClass, 'ID') => []],
                $ownerClassColumn => [$schema->sqlColumnForField($foreignClass, 'ClassName') => []],
                $ownerRelationColumn => $hasOneName,
            ],
            [
                $linkIdColumn . ' = ' . $relationIdColumn,
                // Only set the owner if it isn't already set
                // Don't check class here - see https://github.com/silverstripe/silverstripe-framework/issues/11165
                "$ownerIdColumn = 0 OR $nullCheck",
                $db->nullCheckClause($ownerRelationColumn, true),
            ]
        );
        // Join the table for $foreignClass
        $foreignClassTable = $schema->tableName($foreignClass);
        if ($foreignClassTable !== $baseTable) {
            $update->addInnerJoin($foreignClassTable, $relationIdColumn . ' = ' . $linkIdColumn);
            // If the table for $foreignClass is not its base table, we need to join that as well
            // so we can get the ID and classname.
            $baseForeignTable = $schema->baseDataTable($foreignClass);
            if (!$update->isJoinedTo($baseForeignTable)) {
                $update->addInnerJoin(
                    $baseForeignTable,
                    $db->escapeIdentifier($baseForeignTable . '.ID') . ' = ' . $db->escapeIdentifier($foreignClassTable . '.ID')
                );
            }
            // Add join and where clauses for polymorphic relations so we don't set the wrong owners
            if ($isPolymorphic) {
                $relationClassColumn = $schema->sqlColumnForField($foreignClass, "{$hasOneName}Class");
                $linkClassColumn = $schema->sqlColumnForField($linkClass, 'ClassName');
                $update->addFilterToJoin($foreignClassTable, $relationClassColumn . ' = ' . $linkClassColumn);
                // Make sure we ignore any multi-relational has_one pointing at something other than Link.Owner
                if ($polymorphicSpec[DataObjectSchema::HAS_ONE_MULTI_RELATIONAL] ?? false) {
                    $update->addWhere([$schema->sqlColumnForField($foreignClass, "{$hasOneName}Relation") => 'Owner']);
                }
            }
        }
        $update->execute();
    }

    /**
     * Publishes links unless Link isn't versioned or developers opt out.
     */
    private function publishLinks(): void
    {
        if (Link::has_extension(Versioned::class)) {
            $shouldPublishLinks = true;
            $this->extend('updateShouldPublishLinks', $shouldPublishLinks);
            if ($shouldPublishLinks) {
                $this->print('Publishing links.');
                /** @var Versioned&Link $link */
                foreach (Link::get()->chunkedFetch() as $link) {
                    // Allow developers to skip publishing each link - this allows for scenarios
                    // where links were Versioned in v2/v3 projects.
                    $shouldPublishLink = true;
                    $this->extend('updateShouldPublishLink', $link, $shouldPublishLink);
                    if ($shouldPublishLink) {
                        $link->copyVersionToStage(Versioned::DRAFT, Versioned::LIVE);
                    }
                    $link->destroy();
                }
                $this->print('Publishing complete.');
            } else {
                $this->print('Skipping publish step.');
            }
        } else {
            $this->print('Links are not versioned - skipping publish step due to project-level customisation.');
        }
    }

    /**
     * Check for broken links and output information about them.
     * Doesn't actually check if file or page exists for those link types,
     * this is just about whether there's data there or not.
     */
    private function checkForBrokenLinks(): void
    {
        $this->print('Checking for broken links.');
        // Using draft stage is safe for unversioned links, and ensures we
        // get all relevant data for versioned but unpublished links.
        Versioned::withVersionedMode(function () {
            Versioned::set_reading_mode('Stage.' . Versioned::DRAFT);
            $checkForBrokenLinks = [
                EmailLink::class => [
                    'field' => 'Email',
                    'emptyValue' => [null, ''],
                ],
                ExternalLink::class => [
                    'field' => 'ExternalUrl',
                    'emptyValue' => [null, ''],
                ],
                FileLink::class => [
                    'field' => 'FileID',
                    'emptyValue' => [null, 0],
                ],
                PhoneLink::class => [
                    'field' => 'Phone',
                    'emptyValue' => [null, ''],
                ],
                SiteTreeLink::class => [
                    'field' => 'PageID',
                    'emptyValue' => [null, 0],
                ],
            ];
            $this->extend('updateCheckForBrokenLinks', $checkForBrokenLinks);
            $brokenLinks = [];
            foreach ($checkForBrokenLinks as $class => $data) {
                $field = $data['field'];
                $emptyValue = $data['emptyValue'];
                $ids = DataObject::get($class)->filter([$field => $emptyValue])->column('ID');
                $numBroken = count($ids);
                $this->print("Found $numBroken broken links for the '$class' class.");
                if ($numBroken > 0) {
                    $brokenLinks[$class] = $ids;
                }
            }

            if (empty($brokenLinks)) {
                $this->print('No broken links.');
                return;
            }

            // Output table of broken links
            $this->print('Broken links:');
            if (Director::is_cli()) {
                // Output in a somewhat CLI friendly table.
                // Pad by the length of the longest class name so things align nicely.
                $longestClassLen = max(array_map('strlen', array_keys($brokenLinks)));
                $paddedClassTitle = str_pad('Link class', $longestClassLen);
                $classSeparator = str_repeat('-', $longestClassLen);
                $output = <<< CLI_TABLE
                $paddedClassTitle | IDs of broken links
                $classSeparator | -------------------
                CLI_TABLE;
                foreach ($brokenLinks as $class => $ids) {
                    $paddedClass = str_pad($class, $longestClassLen);
                    $idsString = implode(', ', $ids);
                    $output .= "\n$paddedClass | $idsString";
                }
            } else {
                // Output as an HTML table
                $output = '<table><thead><tr><th>Link class</th><th>IDs of broken links</th></tr></thead><tbody>';
                foreach ($brokenLinks as $class => $ids) {
                    $idsString = implode(', ', $ids);
                    $output .= "<tr><td>$class</td><td>$idsString</td></tr>";
                }
                $output .= '</tbody></table>';
            }
            $this->print($output);
        });
    }

    /**
     * A convenience method for printing a line to the browser or terminal with appropriate line breaks.
     */
    private function print(string $message): void
    {
        $eol = Director::is_cli() ? "\n" : '<br>';
        echo $message . $eol;
    }
}
