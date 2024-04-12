<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tasks;

use LogicException;
use RuntimeException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\BuildTask;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Versioned\Versioned;

/**
 * @deprecated 4.0.0 Will be removed without equivalent functionality.
 */
class LinkFieldMigrationTask extends BuildTask
{
    use MigrationTaskTrait;

    private static $segment = 'linkfield-tov4-migration-task';

    protected $title = 'Linkfield v2/3 to v4 Migration Task';

    protected $description = 'Migrate from silverstripe/linkfield v2 or v3 to v4';

    /**
     * Enable via YAML configuration if you need to run this task
     */
    private static ?bool $is_enabled = false;

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
     * Perform the actual data migration and publish links as appropriate
     */
    public function performMigration(): void
    {
        $this->extend('beforePerformMigration');
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
        $this->extend('afterPerformMigration');
    }

    /**
     * Check if we actually need to migrate anything, and if not give clear output as to why not.
     */
    private function getNeedsMigration(): bool
    {
        $needsMigration = false;
        $baseTable = DataObject::getSchema()->baseDataTable(Link::class);
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
                // Stop migration if the has_one relation still exists
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
}
