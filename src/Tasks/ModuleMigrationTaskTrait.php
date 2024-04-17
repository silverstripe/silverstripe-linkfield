<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tasks;

use RuntimeException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Versioned\Versioned;

/**
 * @deprecated 4.0.0 Will be removed without equivalent functionality.
 */
trait ModuleMigrationTaskTrait
{
    /**
     * List any has_many relations that should be migrated.
     *
     * Keys are the FQCN for the class where the has_many is declared.
     * Values are the name of the old has_one.
     *
     * Example:
     * <code>
     * // SiteConfig had two has_many relationships,
     * // one to Link.MyHasOne and another to Link.DifferentHasOne.
     * SiteConfig::class => [
     *   'LinksListOne' => 'MyHasOne',
     *   'LinksListTwo' => 'DifferentHasOne',
     * ]
     * </code>
     */
    private static array $has_many_links_data = [];

    /**
     * List any many_many relations that should be migrated.
     *
     * Keys are the FQCN for the class where the many_many is declared. See example below for values.
     *
     * Example:
     * <code>
     * // SiteConfig had three many_many relationships:
     * // The table name for "LinksListOne" will be guessed. It wasn't a many_many through and had no extra fields
     * // The table name for "LinksListTwo" will be guessed. It wasn't a many_many through but did have some extra fields
     * // The table name for "LinksListThree" is explicitly provided. It was a many_many through with some extra fields
     * SiteConfig::class => [
     *   'LinksListOne' => null,
     *   'LinksListTwo' => [
     *     'extraFields' => [
     *       'MySort' => 'Sort',
     *     ],
     *   ],
     *   'LinksListThree' => [
     *     'table' => 'App_MyThroughClassTable',
     *     'extraFields' => [
     *       'MySort' => 'Sort',
     *     ],
     *     'through' => [
     *         'from' => 'FromHasOneName',
     *         'to' => 'ToHasOneName',
     *     ],
     *   ],
     * ]
     * </code>
     */
    private static array $many_many_links_data = [];

    /**
     * The table name for the base link model.
     */
    private string $oldTableName;

    /**
     * Check if we actually need to migrate anything, and if not give clear output as to why not.
     */
    private function getNeedsMigration(): bool
    {
        $oldTableName = $this->getTableOrObsoleteTable(static::config()->get('old_link_table'));
        if (!$oldTableName) {
            $this->print('Nothing to migrate - old link table doesn\'t exist.');
            return false;
        }
        $this->oldTableName = $oldTableName;
        return true;
    }

    /**
     * Insert a row into the base Link table for each link, mapping all of the columns
     * that are shared across all link types.
     */
    private function insertBaseRows(): void
    {
        $this->extend('beforeInsertBaseRows');
        $db = DB::get_conn();

        // Get a full map of columns to migrate that applies to all link types
        $baseTableColumnMap = $this->getBaseColumnMap();
        // ClassName will need to be handled per link type
        unset($baseTableColumnMap['ClassName']);

        // Set the correct ClassName based on the type of link.
        // Note that case statements have no abstraction, but are already used elsewhere
        // so should be safe. See DataQuery::getFinalisedQuery() which is used for all
        // DataList queries.
        $classNameSelect = 'CASE ';
        $typeColumn = $db->escapeIdentifier("{$this->oldTableName}.Type");
        foreach (static::config()->get('link_type_columns') as $type => $spec) {
            $toClass = $db->quoteString($spec['class']);
            $type = $db->quoteString($type);
            $classNameSelect .= "WHEN {$typeColumn} = {$type} THEN {$toClass} ";
        }
        $classNameSelect .= 'ELSE ' . $db->quoteString(Link::class) . ' END AS ClassName';

        // Insert rows
        $baseTable = DataObject::getSchema()->baseDataTable(Link::class);
        $quotedBaseTable = $db->escapeIdentifier($baseTable);
        $baseColumns = implode(', ', array_values($baseTableColumnMap));
        $subQuery = SQLSelect::create(
            array_keys($baseTableColumnMap),
            $db->escapeIdentifier($this->oldTableName)
        )->addSelect($classNameSelect)->sql();
        // We can't use the ORM to do INSERT with SELECT, but thankfully
        // the syntax is generic enough that it should work for all SQL databases.
        DB::query("INSERT INTO {$quotedBaseTable} ({$baseColumns}, ClassName) {$subQuery}");
        $this->extend('afterInsertBaseRows');
    }

    /**
     * Insert rows for all link subclasses based on the type of the old link
     */
    private function insertTypeSpecificRows(): void
    {
        $this->extend('beforeInsertTypeSpecificRows');
        $schema = DataObject::getSchema();
        $db = DB::get_conn();
        foreach (static::config()->get('link_type_columns') as $type => $spec) {
            $type = $db->quoteString($type);
            $toClass = $spec['class'];
            $columnMap = $spec['fields'];

            $table = $schema->tableName($toClass);
            $quotedTable = $db->escapeIdentifier($table);
            $baseColumns = implode(', ', array_values($columnMap));
            $subQuery = SQLSelect::create(
                ['ID', ...array_keys($columnMap)],
                $db->escapeIdentifier($this->oldTableName),
                [$db->escapeIdentifier("{$this->oldTableName}.Type") . " = {$type}"]
            )->sql();
            // We can't use the ORM to do INSERT with SELECT, but thankfully
            // the syntax is generic enough that it should work for all SQL databases.
            DB::query("INSERT INTO {$quotedTable} (ID, {$baseColumns}) {$subQuery}");
        }
        $this->extend('afterInsertTypeSpecificRows');
    }

    /**
     * Update the Anchor column for SiteTreeLink
     */
    private function updateSiteTreeRows(): void
    {
        $this->extend('beforeUpdateSiteTreeRows');
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
                $this->extend('updateAnchorAndQueryString', $anchor, $queryString, $oldAnchor);
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
        $this->extend('afterUpdateSiteTreeRows');
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
        $oldTableFields = DB::field_list($this->oldTableName);
        foreach ($linksList as $ownerClass => $relations) {
            foreach ($relations as $hasManyRelation => $hasOneRelation) {
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
                $wasMultiRelational = $wasPolyMorphic && array_key_exists("{$hasOneRelation}Relation", $oldTableFields);
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
                        $whereClause[] = [$db->escapeIdentifier("{$this->oldTableName}.{$hasOneRelation}Class") . " IN ($placeholders)" => $validClasses];
                        if ($wasMultiRelational) {
                            $whereClause[] = [$db->escapeIdentifier("{$this->oldTableName}.{$hasOneRelation}Relation") => $hasManyRelation];
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
                    ->addInnerJoin($this->oldTableName, $db->escapeIdentifier($this->oldTableName . '.ID') . ' = ' . $db->escapeIdentifier("{$table}.ID"))
                    ->addInnerJoin($schema->baseDataTable($ownerClass), $schema->sqlColumnForField($ownerClass, 'ID') . ' = ' . $db->escapeIdentifier("{$this->oldTableName}.{$hasOneRelation}ID"));
                    $update->execute();
                }
            }
        }
        $this->extend('afterMigrateHasManyRelations');
    }

    private function migrateManyManyRelations(): void
    {
        $this->extend('beforeMigrateManyManyRelations');
        $linksList = static::config()->get('many_many_links_data');

        // Exit early if there's nothing to migrate
        if (empty($linksList)) {
            $this->print('No many_many relations to migrate.');
            $this->extend('afterMigrateManyManyRelations');
            return;
        }

        $this->print('Migrating many_many relations.');
        $schema = DataObject::getSchema();
        $db = DB::get_conn();
        $baseLinkTable = $schema->baseDataTable(Link::class);
        $originalOldLinkTable = str_replace('_obsolete_', '', $this->oldTableName);
        foreach ($linksList as $ownerClass => $relations) {
            $ownerBaseTable = $schema->baseDataTable($ownerClass);
            $ownerTable = $schema->tableName($ownerClass);
            foreach ($relations as $manyManyRelation => $spec) {
                $throughSpec = $spec['through'] ?? [];
                if (!empty($throughSpec)) {
                    if (!isset($spec['table'])) {
                        throw new RuntimeException("Must declare the table name for many_many through relation '{$ownerClass}.{$manyManyRelation}'.");
                    }
                    $ownerIdField = $throughSpec['from'] . 'ID';
                    $linkIdField = $throughSpec['to'] . 'ID';
                } else {
                    $ownerIdField = "{$ownerTable}ID";
                    $linkIdField = "{$originalOldLinkTable}ID";
                }
                $extraFields = $spec['extraFields'] ?? [];
                $joinTable = $this->getTableOrObsoleteTable($spec['table'] ?? "{$ownerTable}_{$manyManyRelation}");

                if ($joinTable === null) {
                    throw new RuntimeException("Couldn't find join table for many_many relation '{$ownerClass}.{$manyManyRelation}'.");
                }

                $polymorphicWhereClause = [];
                if (!empty($throughSpec)) {
                    $joinColumns = DB::field_list($joinTable);
                    if (array_key_exists($throughSpec['from'] . 'Class', $joinColumns)) {
                        // For polymorphic relations, don't set the owner for records belonging
                        // to a different class hierarchy.
                        $validClasses = ClassInfo::subclassesFor($ownerClass, true);
                        $placeholders = DB::placeholders($validClasses);
                        $polymorphicClassColumn = $throughSpec['from'] . 'Class';
                        $polymorphicWhereClause = [$db->escapeIdentifier("{$joinTable}.{$polymorphicClassColumn}") . " IN ($placeholders)" => $validClasses];
                    }
                }

                // If the join table for many_many through still has an associated DataObject class,
                // something is very weird and we should throw an error.
                // Most likely the developer just forgot to delete it or didn't run dev/build before running this task.
                if (!empty($throughSpec) && $schema->tableClass($joinTable) !== null) {
                    throw new RuntimeException("Join table '{$joinTable}' for many_many through relation '{$ownerClass}.{$manyManyRelation}' still has a DataObject class.");
                }

                $this->copyDuplicatedLinksInThisRelation($manyManyRelation, $ownerBaseTable, $joinTable, $linkIdField, $ownerIdField, $extraFields, $polymorphicWhereClause);

                $tables = [$baseLinkTable];
                // Include versioned tables if link is versioned
                if (Link::has_extension(Versioned::class)) {
                    $tables[] = "{$baseLinkTable}_Versions";
                    $tables[] = "{$baseLinkTable}_Live";
                }
                foreach ($tables as $table) {
                    $ownerIdColumn = $db->escapeIdentifier($table . '.OwnerID');
                    $nullCheck = $db->nullCheckClause($ownerIdColumn, true);

                    // Set owner fields
                    $assignments = [
                        $ownerIdColumn => [$db->escapeIdentifier("{$ownerBaseTable}.ID") => []],
                        $db->escapeIdentifier("{$table}.OwnerClass") => [$db->escapeIdentifier("{$ownerBaseTable}.ClassName") => []],
                        $db->escapeIdentifier("{$table}.OwnerRelation") => $manyManyRelation,
                    ];
                    // Set extra fields
                    foreach ($extraFields as $fromField => $toField) {
                        $assignments[$db->escapeIdentifier("{$table}.{$toField}")] = [$db->escapeIdentifier("{$joinTable}.{$fromField}") => []];
                    }

                    // Make the update, joining on the join table and base owner table
                    $update = SQLUpdate::create(
                        $db->escapeIdentifier($table),
                        $assignments,
                        [
                            // Don't set if there's already an owner for that link
                            "$ownerIdColumn = 0 OR $nullCheck",
                            $db->nullCheckClause($db->escapeIdentifier($table . '.OwnerRelation'), true),
                            ...$polymorphicWhereClause,
                        ]
                    )->addInnerJoin($joinTable, $db->escapeIdentifier("{$joinTable}.{$linkIdField}") . ' = ' . $db->escapeIdentifier("{$table}.ID"))
                    ->addInnerJoin($ownerBaseTable, $db->escapeIdentifier("{$ownerBaseTable}.ID") . ' = ' . $db->escapeIdentifier("{$joinTable}.{$ownerIdField}"));
                    $update->execute();
                }
                // Drop the join table
                $this->print("Dropping old many_many join table '{$joinTable}'");
                DB::get_conn()->query("DROP TABLE \"{$joinTable}\"");
            }
        }

        $this->extend('afterMigrateManyManyRelations');
    }

    /**
     * Duplicate any links which appear multiple times in a many_many relation
     * and remove the duplicate rows from the join table
     */
    private function copyDuplicatedLinksInThisRelation(
        string $relationName,
        string $ownerBaseTable,
        string $joinTable,
        string $linkIdField,
        string $ownerIdField,
        array $extraFields,
        array $polymorphicWhereClause
    ): void {
        $db = DB::get_conn();
        $schema = DataObject::getSchema();
        $baseLinkTable = $schema->baseDataTable(Link::class);
        $joinLinkIdColumn = $db->escapeIdentifier("{$joinTable}.{$linkIdField}");
        $joinOwnerIdColumn = $db->escapeIdentifier("{$joinTable}.{$ownerIdField}");
        $subclassLinkJoins = [];

        // Prepare subquery that identifies which rows are for duplicate links
        $duplicates = SQLSelect::create(
            $joinLinkIdColumn,
            $db->escapeIdentifier($joinTable),
            $polymorphicWhereClause,
            groupby: $joinLinkIdColumn,
            having: "COUNT({$joinLinkIdColumn}) > 1"
        )->execute();

        // Exit early if there's no duplicates
        if ($duplicates->numRecords() < 1) {
            return;
        }

        // Get selection fields, aliased so they can be dropped straight into a link record
        $selections = [
            'ID' => $joinLinkIdColumn,
            'OwnerClass' => $db->escapeIdentifier("{$ownerBaseTable}.ClassName"),
            'OwnerID' => $db->escapeIdentifier("{$ownerBaseTable}.ID"),
        ];
        // Select additional base columns except where they're mapped as extra fields (e.g. sort may come from manymany)
        foreach ($this->getBaseColumnMap() as $baseField) {
            if ($baseField !== 'ID' && !in_array($baseField, $extraFields)) {
                $selections[$baseField] = $db->escapeIdentifier("{$baseLinkTable}.{$baseField}");
            }
        }
        // Select extra fields, aliased as appropriate
        foreach ($extraFields as $fromField => $toField) {
            $selections[$toField] = $db->escapeIdentifier("{$joinTable}.{$fromField}");
        }
        // Select columns from subclasses (e.g. Email, Phone, etc)
        foreach (static::config()->get('link_type_columns') as $spec) {
            foreach ($spec['fields'] as $subclassField) {
                $selections[$subclassField] = $schema->sqlColumnForField($spec['class'], $subclassField);
                // Make sure we join the subclass table into the query
                $subclassTable = $schema->tableForField($spec['class'], $subclassField);
                if (!array_key_exists($subclassTable, $subclassLinkJoins)) {
                    $subclassLinkJoins[$subclassTable] = $db->escapeIdentifier("{$subclassTable}.ID") . ' = ' . $db->escapeIdentifier("{$baseLinkTable}.ID");
                }
            }
        }

        $toDelete = [];
        $originalLinks = [];
        $currentChunk = 0;
        $chunkSize = static::config()->get('chunk_size');
        $count = $chunkSize;
        $duplicateIDs = implode(', ', $duplicates->column());

        // To ensure this scales well, we'll fetch and duplicate links in chunks.
        while ($count >= $chunkSize) {
            $select = SQLSelect::create(
                $selections,
                $db->escapeIdentifier($joinTable),
                [
                    "{$joinLinkIdColumn} in ({$duplicateIDs})",
                    ...$polymorphicWhereClause,
                ]
            )
            ->addInnerJoin($ownerBaseTable, $db->escapeIdentifier("{$ownerBaseTable}.ID") . " = {$joinOwnerIdColumn}")
            ->addInnerJoin($baseLinkTable, $db->escapeIdentifier("{$baseLinkTable}.ID") . " = {$joinLinkIdColumn}");
            // Add joins for link subclasses
            foreach ($subclassLinkJoins as $subclassTable => $onPredicate) {
                if (!$select->isJoinedTo($subclassTable)) {
                    $select->addLeftJoin($subclassTable, $onPredicate);
                }
            }
            $linkData = $select->setLimit($chunkSize, $chunkSize * $currentChunk)->execute();
            // Prepare for next iteration
            $count = $linkData->numRecords();
            $currentChunk++;

            foreach ($linkData as $link) {
                $ownerID = $link['OwnerID'];
                $linkID = $link['ID'];
                unset($link['ID']);
                // Skip the first of each duplicate set (i.e. the original link)
                if (!array_key_exists($linkID, $originalLinks)) {
                    $originalLinks[$linkID] = true;
                    continue;
                }
                // Mark duplicate join row for deletion
                $toDelete[] = "{$joinOwnerIdColumn} = {$ownerID} AND {$joinLinkIdColumn} = {$linkID}";
                // Create the duplicate link - note it already has its correct owner relation and other necessary data
                $link['OwnerRelation'] = $relationName;
                $newLink = $link['ClassName']::create($link);
                $this->extend('updateNewLink', $newLink, $link);
                $newLink->write();
            }

            // If $chunkSize was null, we did everything in a single chunk
            // but we need to break the loop artificially.
            if ($chunkSize === null) {
                break;
            }
        }

        // Delete the duplicate rows from the join table
        SQLDelete::create($db->escapeIdentifier($joinTable), $polymorphicWhereClause)->addWhereAny($toDelete)->execute();
    }

    /**
     * If the table exists, returns it. If it exists but is obsolete, returned the obsolete
     * prefixed name.
     * Returns null if the table doesn't exist at all.
     */
    private function getTableOrObsoleteTable(string $tableName): ?string
    {
        $allTables = DB::table_list();
        if (!array_key_exists(strtolower($tableName), $allTables)) {
            $tableName = '_obsolete_' . $tableName;
            if (!array_key_exists(strtolower($tableName), $allTables)) {
                return null;
            }
        }
        return $tableName;
    }

    private function getBaseColumnMap(): array
    {
        $baseColumnMap = static::config()->get('base_link_columns');
        foreach (array_keys(DataObject::config()->uninherited('fixed_fields')) as $fixedField) {
            $baseColumnMap[$fixedField] = $fixedField;
        }
        return $baseColumnMap;
    }
}
