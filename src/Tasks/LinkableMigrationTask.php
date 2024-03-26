<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tasks;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\BuildTask;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Versioned\Versioned;

/**
 * @deprecated 4.0.0 Will be removed without equivalent functionality.
 */
class LinkableMigrationTask extends BuildTask
{
    use TemporaryNameTrait;

    private static $segment = 'linkable-migration-task';

    protected $title = 'Linkable Migration Task';

    protected $description = 'Truncate LinkField records and migrate from Linkable records';

    /**
     * Enable via YAML configuration if you need to run this task
     */
    private static ?bool $is_enabled = true;

    /**
     * The number of links to process at once, for operations that operate on each link individually.
     * Processing links in chunks reduces the chance of hitting memory limits.
     * Set to null to process all links in a single chunk.
     */
    private static ?int $chunk_size = 1000;

    /**
     * The name of the table for the Sheadawson\Linkable\Models\Link model.
     *
     * Configurable since it's such a generic name, there's a chance people configured
     * it to something different to avoid collisions.
     */
    private static string $old_link_table = 'LinkableLink';

    /**
     * Mapping for columns in the base link table.
     * Doesn't include
     */
    private static array $base_link_columns = [
        'OpenInNewWindow' => 'OpenInNew',
        'Title' => 'LinkText',
    ];

    /**
     * Mapping for different types of links, including the class to map to and
     * database column mappings.
     */
    private static array $link_type_columns = [
        'URL' => [
            'class' => ExternalLink::class,
            'fields' => [
                'URL' => 'ExternalUrl',
            ],
        ],
        'Email' => [
            'class' => EmailLink::class,
            'fields' => [
                'Email' => 'Email',
            ],
        ],
        'Phone' => [
            'class' => PhoneLink::class,
            'fields' => [
                'Phone' => 'Phone',
            ],
        ],
        'File' => [
            'class' => FileLink::class,
            'fields' => [
                'FileID' => 'FileID',
            ],
        ],
        'SiteTree' => [
            'class' => SiteTreeLink::class,
            'fields' => [
                'SiteTreeID' => 'PageID',
            ],
        ],
    ];

    /**
     * List any has_many relations that should be migrated.
     */
    private static array $has_many_links_data = [];

    /**
     * in-memory cache of Link relational data so we don't keep slamming the filesystem cache
     * when checking these relations in CLI
     */
    private array $linkRelationData = [];

    /**
     * The table name for the base link model.
     */
    private string $oldTableName;

    /**
     * Check if we actually need to migrate anything, and if not give clear output as to why not.
     */
    private function getNeedsMigration(): bool
    {
        $oldTableName = $this->config()->get('old_link_table');
        
        $allTables = DB::table_list();
        if (!in_array(strtolower($oldTableName), array_keys($allTables))) {
            $oldTableName = '_obsolete_' . $oldTableName;
            if (!in_array(strtolower($oldTableName), array_keys($allTables))) {
                $this->print('Nothing to migrate - old link table doesn\'t exist.');
                return false;
            }
        }
        $this->oldTableName = $oldTableName;
        return true;
    }

    /**
     * Perform the actual data migration and publish links as appropriate
     */
    public function performMigration(): void
    {
        $this->insertBaseRows();
        $this->insertTypeSpecificRows();
        $this->updateSiteTreeRows();
        $this->migrateHasManyRelations();
        $this->migrateManyManyRelations(); // todo
        $this->setOwnerForHasOneLinks();

        $this->print("Dropping table {$this->oldTableName}");
        DB::get_conn()->query("DROP TABLE \"{$this->oldTableName}\"");

        $this->print('-----------------');
        $this->print('Bulk data migration complete. All links should be correct (but unpublished) at this stage.');
        $this->print('-----------------');

        $this->publishLinks();

        $this->print('-----------------');
        $this->print('Migration completed successfully.');
        $this->print('-----------------');

        /* @TODO
            - Set owners for old-many_many relations
              - Including sort column mapping
              - Handle regular many_many as well as many_many through!
            - Drop the old table (with extension point to cancel dropping, and extension point for afterwards)
            - Evaluate task and see if more extension points are warranted
        */
    }

    /**
     * Insert a row into the base Link table for each link, mapping all of the columns
     * that are shared across all link types.
     */
    private function insertBaseRows(): void
    {
        $db = DB::get_conn();

        // Get a full map of columns to migrate that applies to all link types
        $baseTableColumnMap = static::config()->get('base_link_columns');
        foreach (array_keys(DataObject::config()->uninherited('fixed_fields')) as $fixedField) {
            // ClassName will need to be handled per link type
            if ($fixedField === 'ClassName') {
                continue;
            }
            $baseTableColumnMap[$fixedField] = $fixedField;
        }

        // Set the correct ClassName based on the type of link.
        // Note that case statements have no abstraction, but are already used elsewhere
        // so should be safe. See DataQuery::getFinalisedQuery() which is used for all
        // DataList queries.
        $classNameSelect = 'CASE ';
        $oldTableName = $this->oldTableName ?? $this->config()->get('old_link_table');
        $typeColumn = $db->escapeIdentifier("{$oldTableName}.Type");
        foreach (static::config()->get('link_type_columns') as $type => $spec) {
            $toClass = $spec['class'];
            $classNameSelect .= "WHEN {$typeColumn} = '{$type}' THEN '{$toClass}' ";
        }
        $classNameSelect .= 'ELSE ' . $db->quoteString(Link::class) . ' END AS ClassName';

        // Insert rows
        $baseTable = DataObject::getSchema()->baseDataTable(Link::class);
        $quotedBaseTable = $db->escapeIdentifier($baseTable);
        $baseColumns = implode(', ', array_values($baseTableColumnMap));
        $subQuery = SQLSelect::create(
            array_keys($baseTableColumnMap),
            $db->escapeIdentifier($oldTableName)
        )->addSelect($classNameSelect)->sql();
        // We can't use the ORM to do INSERT with SELECT, but thankfully
        // the syntax is generic enough that it should work for all SQL databases.
        DB::query("INSERT INTO {$quotedBaseTable} ({$baseColumns}, ClassName) {$subQuery}");
    }

        /**
     * Insert rows for all link subclasses based on the type of the old link
     */
    private function insertTypeSpecificRows(): void
    {
        $schema = DataObject::getSchema();
        $db = DB::get_conn();
        foreach (static::config()->get('link_type_columns') as $type => $spec) {
            $toClass = $spec['class'];
            $columnMap = $spec['fields'];

            $table = $schema->tableName($toClass);
            $quotedTable = $db->escapeIdentifier($table);
            $baseColumns = implode(', ', array_values($columnMap));
            $oldTableName = $this->oldTableName ?? $this->config()->get('old_link_table');
            $subQuery = SQLSelect::create(
                array_keys($columnMap),
                $db->escapeIdentifier($oldTableName),
                [
                    $db->escapeIdentifier($oldTableName . '.Type') . '=' . $db->quoteString($type),
                    $db->nullCheckClause($db->escapeIdentifier($oldTableName . '.Type'), false)
                ]
            )->sql();
            // We can't use the ORM to do INSERT with SELECT, but thankfully
            // the syntax is generic enough that it should work for all SQL databases.
            DB::query("INSERT INTO {$quotedTable} ({$baseColumns}) {$subQuery}");
        }
    }

    /**
     * Update the Anchor column for SiteTreeLink
     */
    private function updateSiteTreeRows(): void
    {
        // We have to split the Anchor column, which means we have to fetch and operate on the values.
        $currentChunk = 0;
        $chunkSize = static::config()->get('chunk_size');
        $count = $chunkSize;
        $db = DB::get_conn();
        $schema = DataObject::getSchema();
        $siteTreeLinkTable = $schema->tableForField(SiteTreeLink::class, 'Anchor');
        // Keep looping until we run out of chunks
        while ($count >= $chunkSize) {
            // Get data about the old SiteTree links
            $oldLinkRows = SQLSelect::create(
                ['ID', 'Anchor'],
                $db->escapeIdentifier($this->oldTableName),
                [
                    $db->escapeIdentifier($this->oldTableName . '.Type') => 'SiteTree',
                    $db->nullCheckClause($db->escapeIdentifier($this->oldTableName . '.Anchor'), false)
                ]
            )->setLimit($chunkSize, $chunkSize * $currentChunk)->execute();
            // Prepare for next iteration
            $count = $oldLinkRows->numRecords();
            $currentChunk++;

            // Update all links which have an anchor
            foreach ($oldLinkRows as $oldLink) {
                // Get the query string and anchor separated
                $queryString = null;
                $anchor = null;
                $oldAnchor = $oldLink['Anchor'];
                if (str_starts_with($oldAnchor, '#')) {
                    $parts = explode('?', $oldAnchor, 2);
                    $anchor = ltrim($parts[0], '#');
                    $queryString = ltrim($parts[1] ?? '', '?');
                } elseif (str_starts_with($oldAnchor, '?')) {
                    $parts = explode('#', $oldAnchor, 2);
                    $queryString = ltrim($parts[0], '?');
                    $anchor = ltrim($parts[1] ?? '', '#');
                } else {
                    // Assume it's an anchor and they just forgot the #
                    // We don't need the # so just add it directly.
                    $anchor = $oldAnchor;
                }
                // Update the link with the correct anchor and query string
                SQLUpdate::create(
                    $db->escapeIdentifier($siteTreeLinkTable),
                    [
                        $schema->sqlColumnForField(SiteTreeLink::class, 'Anchor') => $anchor,
                        $schema->sqlColumnForField(SiteTreeLink::class, 'QueryString') => $queryString,
                    ],
                    [$db->escapeIdentifier($siteTreeLinkTable . '.ID') => $oldLink['ID']]
                )->execute();
            }

            // If $chunkSize was null, we did everything in a single chunk
            // but we need to break the loop artificially.
            if ($chunkSize === null) {
                break;
            }
        }
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
        $oldTableName = $this->oldTableName ?? $this->config()->get('old_link_table');
        $oldTableFields = DB::field_list($oldTableName);
        foreach ($linksList as $ownerClass => $relations) {
            foreach ($relations as $hasManyRelation => $hasOneRelation) {
                $hasOneRelation = is_array($hasOneRelation) ? $hasOneRelation['hasOne'] : $hasOneRelation;
                // Check if HasOneID column is in the old base Link table
                if (!array_key_exists("{$hasOneRelation}ID", $oldTableFields)) {
                    // This is an unusual situation, and is difficult to do generically.
                    // We'll leave this scenario up to the developer to handle.
                    $this->extend('migrateHasOneForLinkSubclass', $linkClass, $ownerClass, $hasOneRelation, $hasManyRelation);
                    continue;
                }
                $linkTable = $schema->baseDataTable(Link::class);
                $tables = [$linkTable];
                // Include versioned tables if link is versioned
                if (Link::has_extension(Versioned::class)) {
                    $tables[] = "{$linkTable}_Versions";
                    $tables[] = "{$linkTable}_Live";
                }
                $wasPolyMorphic = array_key_exists("{$hasOneRelation}Class", $oldTableFields);
                $wasMultiRelational = array_key_exists("{$hasOneRelation}Relation", $oldTableFields);
                // Migrate old has_one on link to the Owner relation.
                foreach ($tables as $table) {
                    // Only set owner where the OwnerID is not already set
                    $ownerIdColumn = $db->escapeIdentifier($table . '.OwnerID');
                    $nullCheck = $db->nullCheckClause($ownerIdColumn, true);
                    $whereClause = [
                        "$ownerIdColumn = 0 OR $nullCheck",
                        $db->nullCheckClause($db->escapeIdentifier($table . '.OwnerRelation'), true),
                    ];
                    if ($wasPolyMorphic) {
                        // For polymorphic relations, don't set the owner for records belonging
                        // to a different class hierarchy.
                        $validClasses = ClassInfo::subclassesFor($ownerClass, true);
                        $placeholders = DB::placeholders($validClasses);
                        $whereClause[] = [$db->escapeIdentifier("{$oldTableName}.{$hasOneRelation}Class") . " IN ($placeholders)" => $validClasses];
                        if ($wasMultiRelational) {
                            $whereClause[] = [$db->escapeIdentifier("{$oldTableName}.{$hasOneRelation}Relation") => $hasManyRelation];
                        }
                    }
                    $update = SQLUpdate::create(
                        $db->escapeIdentifier($table),
                        [
                            $db->escapeIdentifier($table . '.OwnerID') => [$schema->sqlColumnForField($ownerClass, 'ID') => []],
                            $db->escapeIdentifier($table . '.OwnerClass') => [$schema->sqlColumnForField($ownerClass, 'ClassName') => []],
                            $db->escapeIdentifier($table . '.OwnerRelation') => $hasManyRelation,
                        ],
                        $whereClause
                    )
                    ->addInnerJoin($oldTableName, $db->escapeIdentifier($oldTableName . '.ID') . ' = ' . $db->escapeIdentifier("{$table}.ID"))
                    ->addInnerJoin($schema->baseDataTable($ownerClass), $schema->sqlColumnForField($ownerClass, 'ID') . ' = ' . $db->escapeIdentifier("{$this->oldTableName}.{$hasOneRelation}ID"));
                    $update->execute();
                }
            }
        }
        $this->extend('afterMigrateHasManyRelations');
    }

    private function migrateManyManyRelations(): void
    {
        // todo
    }

    private function classIsOldLink(string $class): bool
    {
        return $class === Link::class;
    }
}
