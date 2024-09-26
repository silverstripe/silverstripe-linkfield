<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tests\Tasks;

use LogicException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\LinkField\Tasks\GorriecoeMigrationTask;
use SilverStripe\LinkField\Tests\Models\LinkTest\LinkOwner;
use SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest\WasManyManyJoinModel;
use SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest\WasManyManyOwner;
use SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest\CustomLink;
use SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest\HasManyLinkOwner;
use SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest\WasHasOneLinkOwner;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use Symfony\Component\Console\Output\BufferedOutput;
use PHPUnit\Framework\Attributes\DataProvider;

class GorriecoeMigrationTaskTest extends SapphireTest
{
    private const OLD_LINK_TABLE = 'GorriecoeMigrationTaskTest_OldLinkTable';

    private const TYPE_MAP = [
        'URL' => ExternalLink::class,
        'Email' => EmailLink::class,
        'Phone' => PhoneLink::class,
        'File' => FileLink::class,
        'SiteTree' => SiteTreeLink::class,
        'Custom' => CustomLink::class,
    ];

    protected static $fixture_file = 'GorriecoeMigrationTaskTest.yml';

    protected static $extra_dataobjects = [
        CustomLink::class,
        HasManyLinkOwner::class,
        LinkOwner::class,
        WasManyManyJoinModel::class,
        WasManyManyOwner::class,
        WasHasOneLinkOwner::class,
    ];

    /**
     * Required because of the use of fixtures with a custom table.
     * Without this, the table (and its fixtures) won't be recreated after each test
     * so any test that tears down the table would cause future tests to fail.
     */
    protected $usesTransactions = false;

    private BufferedOutput $buffer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buffer = new BufferedOutput();
        // Add custom link config
        GorriecoeMigrationTask::config()->merge('link_type_columns', [
            'Custom' => [
                'class' => CustomLink::class,
                'fields' => [
                    'CustomField' => 'MyField',
                ],
            ],
        ]);
        GorriecoeMigrationTask::config()->merge('base_link_columns', [
            'MySort' => 'Sort',
        ]);
    }

    public function onBeforeLoadFixtures(): void
    {
        GorriecoeMigrationTask::config()->set('old_link_table', self::OLD_LINK_TABLE);
        // Set up migration tables
        DB::get_schema()->schemaUpdate(function () {
            // Old link table
            $linkDbColumns = [
                ...DataObject::config()->uninherited('fixed_fields'),
                // Fields directly from the Link class
                'Title' => 'Varchar',
                'Type' => 'Varchar(50)',
                'URL' => 'Text',
                'Email' => 'Varchar',
                'Phone' => 'Varchar(30)',
                'OpenInNewWindow' => 'Boolean',
                'SelectedStyle' => 'Varchar',
                'FileID' => 'ForeignKey',
                // Fields from the LinkSiteTree extension
                'Anchor' => 'Varchar(255)',
                'SiteTreeID' => 'ForeignKey',
                // Field for a custom link type
                'CustomField' => 'Varchar',
                // Field for custom sort
                'MySort' => 'Int',
            ];
            DB::require_table(self::OLD_LINK_TABLE, $linkDbColumns, options: DataObject::config()->get('create_table_options'));
            // many_many tables
            $schema = DataObject::getSchema();
            $ownerTable = $schema->tableName(WasManyManyOwner::class);
            $normalJoinColumns = [
                "{$ownerTable}ID" => 'ForeignKey',
                self::OLD_LINK_TABLE . 'ID' => 'ForeignKey',
                'CustomSort' => 'Int',
            ];
            DB::require_table("{$ownerTable}_NormalManyMany", $normalJoinColumns, options: DataObject::config()->get('create_table_options'));
            $throughJoinColumns = [
                'OldOwnerID' => 'ForeignKey',
                'OldLinkID' => 'ForeignKey',
                'CustomSort' => 'Int',
            ];
            DB::require_table('GorriecoeMigrationTaskTest_manymany_through', $throughJoinColumns, options: DataObject::config()->get('create_table_options'));
            $throughPolymorphicJoinColumns = [
                ...$throughJoinColumns,
                // technically it would be a DBClassName enum but this is easier and the actual type doesn't matter
                'OldOwnerClass' => 'Varchar',
            ];
            DB::require_table('GorriecoeMigrationTaskTest_manymany_throughpoly', $throughPolymorphicJoinColumns, options: DataObject::config()->get('create_table_options'));
        });
        parent::onBeforeLoadFixtures();
    }

    public static function provideGetNeedsMigration(): array
    {
        return [
            'no old table' => [
                'hasTable' => false,
                'expected' => false,
            ],
            'original old table' => [
                'hasTable' => true,
                'expected' => true,
            ],
            'obsolete old table' => [
                'hasTable' => 'obsolete',
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('provideGetNeedsMigration')]
    public function testGetNeedsMigration(string|bool $hasTable, bool $expected): void
    {
        if ($hasTable === false) {
            DB::query('DROP TABLE "'. self::OLD_LINK_TABLE .'"');
        } elseif ($hasTable === 'obsolete') {
            $this->startCapturingOutput();
            DB::get_schema()->schemaUpdate(function () {
                DB::dont_require_table(self::OLD_LINK_TABLE);
            });
            $this->startCapturingOutput();
        }

        $result = $this->callPrivateMethod('getNeedsMigration');
        $output = $this->buffer->fetch();
        $this->assertSame($expected, $result);
        $this->assertSame($expected ? '' : "Nothing to migrate - old link table doesn't exist.\n", $output);
    }

    public function testInsertBaseRows(): void
    {
        // Remove existing links which can cause ID conflicts.
        // Note they would have already caused the migration to abort before this point.
        Link::get()->removeAll();

        // Insert the rows
        $this->callPrivateMethod('insertBaseRows');
        $output = $this->buffer->fetch();

        $select = new SQLSelect(from: DB::get_conn()->escapeIdentifier(DataObject::getSchema()->baseDataTable(Link::class)));
        foreach ($select->execute() as $link) {
            // Skip any links that already existed
            if (str_starts_with($link['LinkText'], 'pre-existing')) {
                continue;
            }
            // The owner class is likely to be some arbitrary model - see https://github.com/silverstripe/silverstripe-framework/issues/11165
            unset($link['OwnerClass']);
            $oldLinkSelect = new SQLSelect(from: DB::get_conn()->escapeIdentifier(self::OLD_LINK_TABLE), where: ['ID' => $link['ID']]);
            $oldLinkData = $oldLinkSelect->execute()->record();
            $expectedDataForLink = [
                'ID' => $oldLinkData['ID'],
                'ClassName' => self::TYPE_MAP[$oldLinkData['Type']],
                'LastEdited' => $oldLinkData['LastEdited'],
                'Created' => $oldLinkData['Created'],
                'LinkText' => $oldLinkData['Title'],
                'OpenInNew' => $oldLinkData['OpenInNewWindow'],
                'Sort' => $oldLinkData['MySort'],
                // All of the below are just left as the default values
                'OwnerID' => 0,
                'OwnerRelation' => null,
                'Version' => 0,
            ];
            ksort($expectedDataForLink);
            ksort($link);
            $this->assertSame($expectedDataForLink, $link);
        }

        $this->assertEmpty($output);
    }

    public function testInsertTypeSpecificRows(): void
    {
        // Remove existing links which can cause ID conflicts.
        // Note they would have already caused the migration to abort before this point.
        Link::get()->removeAll();
        // This test is dependent on the base rows being inserted
        $this->callPrivateMethod('insertBaseRows');
        $this->buffer->fetch();

        // Insert the rows
        $this->callPrivateMethod('insertTypeSpecificRows');
        $output = $this->buffer->fetch();

        $oldLinkSelect = new SQLSelect(from: DB::get_conn()->escapeIdentifier(self::OLD_LINK_TABLE));
        $oldLinkData = $oldLinkSelect->execute();
        $this->assertCount($oldLinkData->numRecords(), Link::get());

        $typeColumnMaps = GorriecoeMigrationTask::config()->get('link_type_columns');
        foreach ($oldLinkData as $oldLink) {
            $link = Link::get()->byID($oldLink['ID']);
            $this->assertInstanceOf(self::TYPE_MAP[$oldLink['Type']], $link);
            foreach ($typeColumnMaps[$oldLink['Type']]['fields'] as $oldField => $newField) {
                $this->assertSame(
                    $oldLink[$oldField],
                    $link->$newField,
                    "'$newField' field on Link must be the same as '$oldField' field in the old table"
                );
            }
        }

        $this->assertEmpty($output);
    }

    public function testUpdateSiteTreeRows(): void
    {
        // Remove existing links which can cause ID conflicts.
        // Note they would have already caused the migration to abort before this point.
        Link::get()->removeAll();
        // This test is dependent on the base and type-specific rows being inserted
        $this->callPrivateMethod('insertBaseRows');
        $this->callPrivateMethod('insertTypeSpecificRows');
        $this->buffer->fetch();

        // Update the rows
        $this->callPrivateMethod('updateSiteTreeRows');
        $output = $this->buffer->fetch();

        $oldLinkSelect = new SQLSelect(from: DB::get_conn()->escapeIdentifier(self::OLD_LINK_TABLE));
        foreach (SiteTreeLink::get() as $link) {
            $oldLinkSelect = new SQLSelect(
                from: DB::get_conn()->escapeIdentifier(self::OLD_LINK_TABLE),
                where: ['ID' => $link->ID]
            );
            $oldLink = $oldLinkSelect->execute()->record();
            $oldAnchor = $oldLink['Anchor'];
            if ($oldAnchor === null) {
                $anchor = null;
                $queryString = null;
            } elseif (str_starts_with($oldAnchor, '?')) {
                $anchor = 'anchor-second';
                $queryString = 'querystring=first&awesome';
            } elseif (str_starts_with($oldAnchor, '#')) {
                $anchor = 'anchor-first';
                $queryString = 'querystring=second&awesome';
            } else {
                $anchor = 'this-will-be?treated&like-just-an-anchor=1#okiedoke';
                $queryString = null;
            }
            $this->assertSame($anchor, $link->Anchor, 'Anchor must be set correctly');
            $this->assertSame($queryString, $link->QueryString, 'Query string must be set correctly');
        }

        $this->assertEmpty($output);
    }

    public static function provideMigrateHasManyRelations(): array
    {
        return [
            'no has_many' => [
                'hasManyConfig' => [],
            ],
            'regular has_one' => [
                'hasManyConfig' => [
                    HasManyLinkOwner::class => [
                        'RegularHasMany' => 'OldHasOne',
                    ],
                ],
                'ownerFixture' => 'legacy-relations',
                'addColumns' => ['OldHasOneID' => DBInt::class],
            ],
            'polymorphic has_one' => [
                'hasManyConfig' => [
                    HasManyLinkOwner::class => [
                        'PolyHasMany' => 'OldHasOne',
                    ],
                ],
                'ownerFixture' => 'legacy-relations',
                'addColumns' => [
                    'OldHasOneID' => DBInt::class,
                    'OldHasOneClass' => DBVarchar::class,
                ],
            ],
        ];
    }

    #[DataProvider('provideMigrateHasManyRelations')]
    public function testMigrateHasManyRelations(
        array $hasManyConfig,
        string $ownerFixture = null,
        array $addColumns = []
    ): void {
        GorriecoeMigrationTask::config()->set('has_many_links_data', $hasManyConfig);

        if (!empty($addColumns) && !$ownerFixture) {
            throw new LogicException('Test scenario is broken - need owner if we are adding columns.');
        }

        // Set up legacy has_one columns and data
        if ($ownerFixture) {
            $oldTable = self::OLD_LINK_TABLE;
            DB::get_schema()->schemaUpdate(function () use ($oldTable, $addColumns) {
                foreach ($addColumns as $column => $fieldType) {
                    $dbField = DBField::create_field($fieldType, null, $column);
                    $dbField->setTable($oldTable);
                    $dbField->requireField();
                }
            });
            $db = DB::get_conn();
            $ownerClass = array_key_first($hasManyConfig);
            $owner = $this->objFromFixture($ownerClass, $ownerFixture);
            foreach (array_keys($addColumns) as $columnName) {
                $value = str_ends_with($columnName, 'ID') ? $owner->ID : $owner->ClassName;
                SQLUpdate::create(
                    $db->escapeIdentifier($oldTable),
                    [$db->escapeIdentifier("{$oldTable}.{$columnName}") => $value]
                )->execute();
            }
        }

        // Run the migration
        $this->callPrivateMethod('migrateHasManyRelations');
        $output = $this->buffer->fetch();

        if (empty($hasManyConfig)) {
            $this->assertSame("No has_many relations to migrate.\n", $output);
            return;
        }

        $expectedOutput = "Migrating has_many relations.\n";

        // Owner SHOULD have been set
        foreach ($hasManyConfig as $ownerClass => $relationData) {
            $owner = $this->objFromFixture($ownerClass, $ownerFixture);
            foreach ($relationData as $hasManyRelation => $spec) {
                $list = $owner->$hasManyRelation();
                // Check that the Owner relation got set correctly for these
                $this->assertSame([$owner->ID], $list->columnUnique('OwnerID'));
                $this->assertSame([$hasManyRelation], $list->columnUnique('OwnerRelation'));
                $this->assertSame([$owner->ClassName], $list->columnUnique('OwnerClass'));
            }
        }

        $this->assertSame($expectedOutput, $output);
    }

    public static function provideMigrateManyManyRelations(): array
    {
        return [
            'no relations' => [
                'manymanyConfig' => [],
            ],
            'normal many_many, nothing specified' => [
                'manymanyConfig' => [
                    WasManyManyOwner::class => [
                        'NormalManyMany' => null,
                    ],
                ],
            ],
            'normal many_many, fully specified' => [
                'manymanyConfig' => [
                    WasManyManyOwner::class => [
                        'NormalManyMany' => [
                            'table' => 'LinkFieldTest_Tasks_WasManyManyOwner_NormalManyMany',
                            'extraFields' => [
                                'CustomSort' => 'Sort',
                            ],
                        ],
                    ],
                ],
            ],
            'many_many through' => [
                'manymanyConfig' => [
                    WasManyManyOwner::class => [
                        'ManyManyThrough' => [
                            'table' => 'GorriecoeMigrationTaskTest_manymany_through',
                            'extraFields' => [
                                'CustomSort' => 'Sort',
                            ],
                            'through' => [
                                'from' => 'OldOwner',
                                'to' => 'OldLink',
                            ],
                        ],
                    ],
                ],
            ],
            'many_many through' => [
                'manymanyConfig' => [
                    WasManyManyOwner::class => [
                        'ManyManyThroughPolymorphic' => [
                            'table' => 'GorriecoeMigrationTaskTest_manymany_throughpoly',
                            'extraFields' => [
                                'CustomSort' => 'Sort',
                            ],
                            'through' => [
                                'from' => 'OldOwner',
                                'to' => 'OldLink',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('provideMigrateManyManyRelations')]
    public function testMigrateManyManyRelations(array $manymanyConfig): void
    {
        GorriecoeMigrationTask::config()->set('many_many_links_data', $manymanyConfig);

        // Run the migration
        $this->callPrivateMethod('migrateManyManyRelations');
        $output = $this->buffer->fetch();

        if (empty($manymanyConfig)) {
            $this->assertSame("No many_many relations to migrate.\n", $output);
            return;
        }

        $expectedOutput = "Migrating many_many relations.\n";

        foreach ($manymanyConfig as $config) {
            foreach ($config as $relation => $spec) {
                $table = $spec['table'] ?? 'LinkFieldTest_Tasks_WasManyManyOwner_NormalManyMany';
                $hasSort = !empty($spec['extraFields']);
                $expectedOutput .= "Dropping old many_many join table '{$table}'\n";

                $owner1 = $this->objFromFixture(WasManyManyOwner::class, 'manymany-owner1');
                $owner2 = $this->objFromFixture(WasManyManyOwner::class, 'manymany-owner2');
                $owner3 = $this->objFromFixture(WasManyManyOwner::class, 'manymany-owner3');

                // Check we have the right amount of owned links
                $this->assertCount(3, $owner1->$relation());
                $this->assertCount(3, $owner2->$relation());
                $this->assertCount(1, $owner3->$relation());

                // Check the links have the correct data
                $emailLink1 = $this->objFromFixture(EmailLink::class, 'email-link01');
                $emailLink1Fields = $emailLink1->toMap();
                $relatedItem1Owner1 = $owner1->$relation();
                $relatedItem1Owner2 = $owner2->$relation();
                $relatedItem1Owner3 = $owner3->$relation();
                $this->assertListContains([$this->setSortInRecord($emailLink1Fields, 1, $hasSort)], $relatedItem1Owner1);
                // These fields will vary for the other owner's links
                unset($emailLink1Fields['ID']);
                unset($emailLink1Fields['OwnerID']);
                unset($emailLink1Fields['LastEdited']);
                $this->assertListContains([$this->setSortInRecord($emailLink1Fields, 3, $hasSort)], $relatedItem1Owner2);
                $this->assertListContains([$this->setSortInRecord($emailLink1Fields, 4, $hasSort)], $relatedItem1Owner3);

                $emailLink2 = $this->objFromFixture(EmailLink::class, 'email-link02');
                $emailLink2Fields = $emailLink2->toMap();
                $relatedItem2Owner1 = $owner1->$relation();
                $relatedItem2Owner2 = $owner2->$relation();
                $this->assertListContains([$this->setSortInRecord($emailLink2Fields, 2, $hasSort)], $relatedItem2Owner1);
                // These fields will vary for the other owner's link
                unset($emailLink2Fields['ID']);
                unset($emailLink2Fields['OwnerID']);
                unset($emailLink2Fields['LastEdited']);
                $this->assertListContains([$this->setSortInRecord($emailLink2Fields, 1, $hasSort)], $relatedItem2Owner2);

                $sitetreeLink1 = $this->objFromFixture(SiteTreeLink::class, 'sitetree-link01');
                $this->assertListContains([$this->setSortInRecord($sitetreeLink1->toMap(), 3, $hasSort)], $owner1->$relation());
                $sitetreeLink2 = $this->objFromFixture(SiteTreeLink::class, 'sitetree-link02');
                $this->assertListContains([$this->setSortInRecord($sitetreeLink2->toMap(), 2, $hasSort)], $owner2->$relation());

                // Check table was dropped
                $this->assertArrayNotHasKey(strtolower($table), DB::table_list());
            }
        }

        $this->assertSame($expectedOutput, $output);
    }

    public static function provideMigrateManyManyRelationsExceptions(): array
    {
        $ownerClass = WasManyManyOwner::class;
        return [
            'join table required' => [
                'config' => [
                    WasManyManyOwner::class => [
                        'ManyManyThrough' => [
                            'through' => [
                                'from' => 'OldOwner',
                                'to' => 'OldLink',
                            ],
                        ],
                    ],
                ],
                'expectedMessage' => "Must declare the table name for many_many through relation '{$ownerClass}.ManyManyThrough'.",
            ],
            'join table not in db' => [
                'config' => [
                    WasManyManyOwner::class => [
                        'ManyManyThrough' => [
                            'table' => 'non-existant table',
                        ],
                    ],
                ],
                'expectedMessage' => "Couldn't find join table for many_many relation '{$ownerClass}.ManyManyThrough'.",
            ],
            'join class still exists' => [
                'config' => [
                    WasManyManyOwner::class => [
                        'ManyManyThrough' => [
                            'table' => 'LinkFieldTest_Tasks_WasManyManyJoinModel',
                            'through' => [
                                'from' => 'OldOwner',
                                'to' => 'OldLink',
                            ],
                        ],
                    ],
                ],
                'expectedMessage' => "Join table 'LinkFieldTest_Tasks_WasManyManyJoinModel' for many_many through relation '{$ownerClass}.ManyManyThrough' still has a DataObject class.",
            ],
        ];
    }

    #[DataProvider('provideMigrateManyManyRelationsExceptions')]
    public function testMigrateManyManyRelationsExceptions(array $config, string $expectedMessage): void
    {
        GorriecoeMigrationTask::config()->set('many_many_links_data', $config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($expectedMessage);

        // Run the migration
        try {
            $this->callPrivateMethod('migrateManyManyRelations');
        } finally {
            // If an exception is thrown we still need to make sure we stop capturing output!
            $this->buffer->fetch();
        }
    }

    public function testSetOwnerForHasOneLinks(): void
    {
        // Remove existing links which can cause ID conflicts.
        // Note they would have already caused the migration to abort before this point.
        Link::get()->removeAll();
        // This test is dependent on the base rows being inserted
        $this->callPrivateMethod('insertBaseRows');
        $this->buffer->fetch();
        // Insert the has_one Owner's rows
        $this->callPrivateMethod('setOwnerForHasOneLinks');
        $output = $this->buffer->fetch();

        $ownerClass = WasHasOneLinkOwner::class;
        $fixtureRelationsHaveLink = [
            'owner-1' => ['Link' => true],
            'owner-2' => ['Link' => true],
            'owner-3' => ['Link' => true],
        ];

        $relationsByID = [];
        foreach ($fixtureRelationsHaveLink as $fixture => $data) {
            $data['fixture'] = $fixture;
            $relationsByID[$this->idFromFixture($ownerClass, $fixture)] = $data;
        }

        foreach (DataObject::get($ownerClass) as $owner) {
            if (array_key_exists($owner->ID, $relationsByID)) {
                $data = $relationsByID[$owner->ID];
                $ownerFixture = $data['fixture'];
                $record = $this->objFromFixture($ownerClass, $ownerFixture);
                foreach ($data as $relation => $hasLink) {
                    if ($relation === 'fixture') {
                        continue;
                    }
                    /** @var Link $link */
                    $link = $record->$relation();
                    if ($hasLink === null) {
                        // Relation should either not be for link, or should not be in the DB.
                        if (is_a($link->ClassName, Link::class, true)) {
                            $this->assertFalse($link->isInDB(), "Relation {$relation} should not have a link saved in it");
                        }
                        continue;
                    } elseif ($hasLink === false) {
                        // The special case is where the Owner relation was already set to a different record.
                        $isSpecialCase = $ownerClass === WasHasOneLinkOwner::class && $ownerFixture === 'owns-has-one-again';
                        // Relation should be for link, but should not have its Owner set.
                        $this->assertTrue($link->isInDB(), "Relation {$relation} should have a link saved in it");
                        // Can't check OwnerClass here - see https://github.com/silverstripe/silverstripe-framework/issues/11165
                        $this->assertSame(
                            [
                                $isSpecialCase ? $this->idFromFixture(WasHasOneLinkOwner::class, 'owns-has-one') : 0,
                                $isSpecialCase ? 'Link' : null
                            ],
                            [
                                $link->OwnerID,
                                $link->OwnerRelation,
                            ],
                            "Relation {$relation} should not have an Owner set"
                        );
                        continue;
                    }
                    $this->assertTrue($link->isInDB(), "Relation {$relation} should have a link saved in it");
                    $this->assertSame(
                        [
                            $owner->ID,
                            $owner->ClassName,
                            $relation,
                        ],
                        [
                            $link->OwnerID,
                            $link->OwnerClass,
                            $link->OwnerRelation,
                        ],
                        "Relation {$relation} should have an Owner set"
                    );
                }
            }
        }

        $this->assertSame("Setting owners for has_one relations.\n", $output);
    }

    private function setSortInRecord(array $record, int $sort, bool $hasSort): array
    {
        if (!$hasSort) {
            return $record;
        }
        $record['Sort'] = $sort;
        return $record;
    }

    private function startCapturingOutput(): void
    {
        flush();
        ob_start();
    }

    private function stopCapturingOutput(): string
    {
        $ret = ob_get_clean();
        flush();
        return $ret;
    }

    private function callPrivateMethod(string $methodName, array $args = []): mixed
    {
        $task = new GorriecoeMigrationTask();
        $output = new PolyOutput(PolyOutput::FORMAT_ANSI, wrappedOutput: $this->buffer);
        $reflectionProperty = new ReflectionProperty($task, 'output');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($task, $output);

        // getNeedsMigration() sets the table to pull from.
        // If we're not testing that method, we need to set the table ourselves.
        if ($this->name() !== 'testGetNeedsMigration') {
            $reflectionProperty = new ReflectionProperty($task, 'oldTableName');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($task, self::OLD_LINK_TABLE);
        }
        $reflectionMethod = new ReflectionMethod($task, $methodName);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invoke($task, ...$args);
    }
}
