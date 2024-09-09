<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tests\Tasks;

use LogicException;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\LinkField\Tasks\LinkFieldMigrationTask;
use SilverStripe\LinkField\Tests\Models\LinkTest\LinkOwner;
use SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest\AmbiguousLinkOwner;
use SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest\CustomLink;
use SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest\CustomLinkMigrationExtension;
use SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest\HasManyLinkOwner;
use SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest\MultiLinkOwner;
use SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest\OverrideMigrationStepsExtension;
use SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest\PolymorphicLinkOwner;
use SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest\ReciprocalLinkOwner;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBInt;
use SilverStripe\ORM\FieldType\DBVarchar;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\DomCrawler\Crawler;

class LinkFieldMigrationTaskTest extends SapphireTest
{
    protected static $fixture_file = 'LinkFieldMigrationTaskTest.yml';

    protected static $extra_dataobjects = [
        CustomLink::class,
        AmbiguousLinkOwner::class,
        HasManyLinkOwner::class,
        LinkOwner::class,
        MultiLinkOwner::class,
        ReciprocalLinkOwner::class,
        PolymorphicLinkOwner::class,
    ];

    protected static $required_extensions = [
        LinkFieldMigrationTask::class => [
            OverrideMigrationStepsExtension::class,
            CustomLinkMigrationExtension::class,
        ]
    ];

    /**
     * Required because of all the creations and deletions of columns.
     * Without this, tests pass individually but fail when run as a class.
     */
    protected $usesTransactions = false;

    private bool $needsResetSchema = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Remove AmbiguousLinkOwner class from class manifest for all tests that it interferes with.
        $name = $this->getName(false);
        if (str_starts_with($name, 'testSetOwnerForHasOneLinks') && $name !== 'testSetOwnerForHasOneLinksAmbiguous') {
            $this->needsResetSchema = true;
            $classManifest = ClassLoader::inst()->getManifest();
            $reflectionGetState = new ReflectionMethod($classManifest, 'getState');
            $reflectionGetState->setAccessible(true);
            $state = $reflectionGetState->invoke($classManifest);
            $lowerCaseClass = strtolower(AmbiguousLinkOwner::class);
            foreach (array_keys($state) as $property) {
                switch ($property) {
                    case 'descendants':
                        unset($state[$property][$lowerCaseClass]);
                        unset($state[$property][strtolower(DataObject::class)][$lowerCaseClass]);
                        break;
                    case 'classes':
                    case 'classNames':
                        unset($state[$property][$lowerCaseClass]);
                        break;
                }
            }
            $reflectionLoadState = new ReflectionMethod($classManifest, 'loadState');
            $reflectionLoadState->setAccessible(true);
            $reflectionLoadState->invokeArgs($classManifest, [$state]);
        }
    }

    protected function tearDown(): void
    {
        // Reset static properties
        OverrideMigrationStepsExtension::$shouldPublishLink = null;
        OverrideMigrationStepsExtension::$shouldPublishLinks = null;
        OverrideMigrationStepsExtension::$needsMigration = null;
        OverrideMigrationStepsExtension::$shouldMigrateColumn = [];
        OverrideMigrationStepsExtension::$shouldDropColumn = [];
        // Add versioning back - should probably be done by whatever flushes cache, but apparently not.
        if (!Link::has_extension(Versioned::class)) {
            Link::add_extension(Versioned::class);
        }
        // Make sure AmbiguousLinkOwner class exists
        if ($this->needsResetSchema) {
            ClassLoader::inst()->getManifest()->regenerate(true);
            DataObject::getSchema()->reset();
        }
        parent::tearDown();
    }

    public function provideGetNeedsMigration(): array
    {
        $scenarios = [
            'has both columns' => [
                'hasTitleColumn' => true,
                'hasLinkTextColumn' => true,
                'extensionOverride' => null,
                'expected' => true,
            ],
            'missing Title column' => [
                'hasTitleColumn' => false,
                'hasLinkTextColumn' => true,
                'extensionOverride' => null,
                'expected' => false,
            ],
            'missing LinkText column' => [
                'hasTitleColumn' => true,
                'hasLinkTextColumn' => false,
                'extensionOverride' => null,
                'expected' => false,
            ],
            'missing both columns' => [
                'hasTitleColumn' => false,
                'hasLinkTextColumn' => false,
                'extensionOverride' => null,
                'expected' => false,
            ],
        ];
        foreach ($scenarios as $title => $scenario) {
            $scenario['extensionOverride'] = true;
            $scenario['expected'] = true;
            $scenarios[$title . ' (override to true)'] = $scenario;
            $scenario['extensionOverride'] = false;
            $scenario['expected'] = false;
            $scenarios[$title . ' (override to false)'] = $scenario;
        }
        return $scenarios;
    }

    /**
     * @dataProvider provideGetNeedsMigration
     */
    public function testGetNeedsMigration(bool $hasTitleColumn, bool $hasLinkTextColumn, ?bool $extensionOverride, bool $expected): void
    {
        // Add/remove columns as necessary to replicate migration scenario
        $baseTable = DataObject::getSchema()->baseDataTable(Link::class);
        if (!$hasLinkTextColumn) {
            DB::get_conn()->query("ALTER TABLE \"$baseTable\" DROP COLUMN \"LinkText\"");
        }
        if ($hasTitleColumn) {
            $this->ensureColumnsExist(['Title' => DBVarchar::class], false);
        }
        // Set extension to override as appropriate
        OverrideMigrationStepsExtension::$needsMigration = $extensionOverride;

        $this->startCapturingOutput();
        $needsMigration = $this->callPrivateMethod('getNeedsMigration');
        $output = $this->stopCapturingOutput();

        $this->assertSame($expected, $needsMigration);

        // Extension overrides can affect the output
        $expectedOutput = '';
        if ($extensionOverride && (!$hasLinkTextColumn || !$hasTitleColumn)) {
            $expectedOutput = "Not skipping migration due to project-level customisation.\n";
        } elseif ($extensionOverride === false && $hasLinkTextColumn && $hasTitleColumn) {
            $expectedOutput = "Skipping migration due to project-level customisation.\n";
        }

        // Output should indicate why migration can't be performed
        if (!$hasLinkTextColumn && !$hasTitleColumn) {
            $expectedOutput .= "Missing multiple columns in the database. This usually happens in new installations before dev/build.\n";
        } elseif (!$hasLinkTextColumn) {
            $expectedOutput .= 'Missing "LinkText" column in database. This usually means you need to upgrade your silverstripe/linkfield dependency and run dev/build.' . "\n";
        } elseif (!$hasTitleColumn) {
            $expectedOutput .= 'Missing "Title" column in database. This usually means you have already run this task or do not need to migrate.' . "\n";
        }

        $this->assertSame($expectedOutput, $output);
    }

    public function provideMigrateTitleColumnUnversioned(): array
    {
        return [
            'full migration' => [
                'skipMigration' => false,
                'skipDropColumn' => false,
                'v3IsVersioned' => false,
            ],
            'skip migration' => [
                'skipMigration' => true,
                'skipDropColumn' => false,
                'v3IsVersioned' => false,
            ],
            'skip drop column' => [
                'skipMigration' => false,
                'skipDropColumn' => true,
                'v3IsVersioned' => false,
            ],
            'full migration, v3 is versioned' => [
                'skipMigration' => false,
                'skipDropColumn' => false,
                'v3IsVersioned' => true,
            ]
        ];
    }

    /**
     * Tests migrating data when v2 and v3 were NOT versioned
     * @dataProvider provideMigrateTitleColumnUnversioned
     */
    public function testMigrateTitleColumnUnversioned(bool $skipMigration, bool $skipDropColumn, bool $v3IsVersioned): void
    {
        // Make sure the Title column exists before we start
        $this->ensureColumnsExist(['Title' => DBVarchar::class], false);

        $schema = DataObject::getSchema();
        $baseTable = $schema->baseDataTable(Link::class);
        if ($skipMigration) {
            OverrideMigrationStepsExtension::$shouldMigrateColumn[$baseTable] = false;
        }
        if ($skipDropColumn) {
            OverrideMigrationStepsExtension::$shouldDropColumn[$baseTable] = false;
        }
        // Add content to the "Title" column for various links
        $ids = [
            'test-email-link01' => $this->idFromFixture(EmailLink::class, 'test-email-link01'),
            'test-email-link02' => $this->idFromFixture(EmailLink::class, 'test-email-link02'),
            'test-email-link03' => $this->idFromFixture(EmailLink::class, 'test-email-link03'),
            'test-external-link01' => $this->idFromFixture(ExternalLink::class, 'test-external-link01'),
            'test-external-link02' => $this->idFromFixture(ExternalLink::class, 'test-external-link02'),
            'test-external-link03' => $this->idFromFixture(ExternalLink::class, 'test-external-link03'),
            'test-file-link01' => $this->idFromFixture(FileLink::class, 'test-file-link01'),
            'test-file-link02' => $this->idFromFixture(FileLink::class, 'test-file-link02'),
            'test-file-link03' => $this->idFromFixture(FileLink::class, 'test-file-link03'),
            'test-phone-link01' => $this->idFromFixture(PhoneLink::class, 'test-phone-link01'),
            'test-phone-link02' => $this->idFromFixture(PhoneLink::class, 'test-phone-link02'),
            'test-phone-link03' => $this->idFromFixture(PhoneLink::class, 'test-phone-link03'),
            'test-sitetree-link01' => $this->idFromFixture(SiteTreeLink::class, 'test-sitetree-link01'),
            'test-sitetree-link02' => $this->idFromFixture(SiteTreeLink::class, 'test-sitetree-link02'),
            'test-sitetree-link03' => $this->idFromFixture(SiteTreeLink::class, 'test-sitetree-link03'),
            'test-custom-link01' => $this->idFromFixture(CustomLink::class, 'test-custom-link01'),
            'test-custom-link02' => $this->idFromFixture(CustomLink::class, 'test-custom-link02'),
            'test-custom-link03' => $this->idFromFixture(CustomLink::class, 'test-custom-link03'),
        ];
        // e.g. for a link with ID=1, the title will be "link title #1"
        $db = DB::get_conn();
        $titleColumn = $db->escapeIdentifier($baseTable . '.Title');
        $idColumn = $schema->sqlColumnForField(Link::class, 'ID');
        $placeholders = DB::placeholders($ids);
        $update = new SQLUpdate(
            $db->escapeIdentifier($baseTable),
            [$titleColumn => ["CONCAT('link title #', $idColumn)" => []]],
            ["$idColumn in ($placeholders)" => array_values($ids)]
        );
        $update->execute();

        if ($this->usesTransactions) {
            // This is required to allow SQLUpdate changes to be rolled back on tearDown
            static::tempDB()->startTransaction();
        }
        if (!$v3IsVersioned) {
            Link::remove_extension(Versioned::class);
        }
        $this->startCapturingOutput();
        $this->callPrivateMethod('migrateTitleColumn');
        $output = $this->stopCapturingOutput();

        // Test output, which will be different if we're skipping things
        $expectedOutput = "Migrating data in '$baseTable' table.\n";
        if ($skipMigration) {
            $expectedOutput .= "Skipping migration of '{$baseTable}.Title' column due to project-level customisation.\n";
        } else {
            if ($skipDropColumn) {
                $expectedOutput .= "Skipping dropping '{$baseTable}.Title' column due to project-level customisation.\n";
            } else {
                $expectedOutput .= "Dropping '{$baseTable}.Title' column.\n";
            }
        }
        if ($v3IsVersioned) {
            $expectedOutput .= "Nothing to migrate in '{$baseTable}_Versions' table.\n";
            $expectedOutput .= "Nothing to migrate in '{$baseTable}_Live' table.\n";
        }
        $this->assertSame($expectedOutput, $output);

        // Test LinkText values
        foreach ($ids as $fixtureName => $id) {
            if (str_ends_with($fixtureName, '01')) {
                // These fixtures already had LinkText, which should not have changed.
                $expectedLinkText = $fixtureName;
            } elseif ($skipMigration) {
                $expectedLinkText = null;
            } else {
                $expectedLinkText = 'link title #' . $id;
            }
            $this->assertSame($expectedLinkText, Link::get()->byID($id)->LinkText);
        }

        // Test whether Title column was dropped
        if ($skipMigration || $skipDropColumn) {
            $this->assertTrue(
                in_array('Title', array_keys(DB::field_list($baseTable))),
                'Title column should NOT be removed'
            );
        } else {
            $this->assertFalse(
                in_array('Title', array_keys(DB::field_list($baseTable))),
                'Title column should be removed'
            );
        }
    }

    public function provideMigrateTitleColumnVersioned(): array
    {
        return [
            'full migration' => [
                'skipMigration' => null,
                'skipDropColumn' => null,
            ],
            'skip migration for Versions table' => [
                'skipMigration' => '_Versions',
                'skipDropColumn' => null,
            ],
            'skip drop column for Versions table' => [
                'skipMigration' => null,
                'skipDropColumn' => '_Versions',
            ],
        ];
    }

    /**
     * Tests migrating data when v2 and v3 WERE versioned
     * @dataProvider provideMigrateTitleColumnVersioned
     */
    public function testMigrateTitleColumnVersioned(?string $skipMigration, ?string $skipDropColumn): void
    {
        // Publish all links before we start so there's data in the _Versions and _Live columns
        Link::get()->each(fn(Link $link) => $link->publishSingle());

        // Make sure the Title column exists before we start
        $this->ensureColumnsExist(['Title' => DBVarchar::class]);

        $schema = DataObject::getSchema();
        $baseTable = $schema->baseDataTable(Link::class);
        if ($skipMigration) {
            OverrideMigrationStepsExtension::$shouldMigrateColumn[$baseTable . $skipMigration] = false;
        }
        if ($skipDropColumn) {
            OverrideMigrationStepsExtension::$shouldDropColumn[$baseTable . $skipDropColumn] = false;
        }
        // Add content to the "Title" column for various links
        $ids = [
            'test-email-link01' => $this->idFromFixture(EmailLink::class, 'test-email-link01'),
            'test-email-link02' => $this->idFromFixture(EmailLink::class, 'test-email-link02'),
            'test-email-link03' => $this->idFromFixture(EmailLink::class, 'test-email-link03'),
            'test-external-link01' => $this->idFromFixture(ExternalLink::class, 'test-external-link01'),
            'test-external-link02' => $this->idFromFixture(ExternalLink::class, 'test-external-link02'),
            'test-external-link03' => $this->idFromFixture(ExternalLink::class, 'test-external-link03'),
            'test-file-link01' => $this->idFromFixture(FileLink::class, 'test-file-link01'),
            'test-file-link02' => $this->idFromFixture(FileLink::class, 'test-file-link02'),
            'test-file-link03' => $this->idFromFixture(FileLink::class, 'test-file-link03'),
            'test-phone-link01' => $this->idFromFixture(PhoneLink::class, 'test-phone-link01'),
            'test-phone-link02' => $this->idFromFixture(PhoneLink::class, 'test-phone-link02'),
            'test-phone-link03' => $this->idFromFixture(PhoneLink::class, 'test-phone-link03'),
            'test-sitetree-link01' => $this->idFromFixture(SiteTreeLink::class, 'test-sitetree-link01'),
            'test-sitetree-link02' => $this->idFromFixture(SiteTreeLink::class, 'test-sitetree-link02'),
            'test-sitetree-link03' => $this->idFromFixture(SiteTreeLink::class, 'test-sitetree-link03'),
            'test-custom-link01' => $this->idFromFixture(CustomLink::class, 'test-custom-link01'),
            'test-custom-link02' => $this->idFromFixture(CustomLink::class, 'test-custom-link02'),
            'test-custom-link03' => $this->idFromFixture(CustomLink::class, 'test-custom-link03'),
        ];
        // e.g. for a link with ID=1, the title will be "link title #1"
        $db = DB::get_conn();
        $titleColumn = $db->escapeIdentifier($baseTable . '.Title');
        $idColumn = $schema->sqlColumnForField(Link::class, 'ID');
        $placeholders = DB::placeholders($ids);
        foreach (['', '_Versions', '_Live'] as $suffix) {
            $versionedTable = $baseTable . $suffix;
            $versionedTitleColumn = str_replace($baseTable, $versionedTable, $titleColumn);
            $versionedIdColumn = str_replace($baseTable, $versionedTable, $idColumn);
            $update = new SQLUpdate(
                $db->escapeIdentifier($versionedTable),
                [$versionedTitleColumn => ["CONCAT('link title #', $versionedIdColumn)" => []]],
                ["$versionedIdColumn in ($placeholders)" => array_values($ids)]
            );
            $update->execute();
        }

        if ($this->usesTransactions) {
            // This is required to allow SQLUpdate changes to be rolled back on tearDown
            static::tempDB()->startTransaction();
        }
        $this->startCapturingOutput();
        $this->callPrivateMethod('migrateTitleColumn');
        $output = $this->stopCapturingOutput();

        // Test output, which will be different if we're skipping things
        $expectedOutput = '';
        foreach (['', '_Versions', '_Live'] as $suffix) {
            $expectedOutput .= "Migrating data in '{$baseTable}{$suffix}' table.\n";
            if ($skipMigration === $suffix) {
                $expectedOutput .= "Skipping migration of '{$baseTable}{$suffix}.Title' column due to project-level customisation.\n";
            } else {
                if ($skipDropColumn === $suffix) {
                    $expectedOutput .= "Skipping dropping '{$baseTable}{$suffix}.Title' column due to project-level customisation.\n";
                } else {
                    $expectedOutput .= "Dropping '{$baseTable}{$suffix}.Title' column.\n";
                }
            }
        }
        $this->assertSame($expectedOutput, $output);

        // Test LinkText values
        foreach ($ids as $fixtureName => $id) {
            foreach (['', '_Versions', '_Live'] as $suffix) {
                if (str_ends_with($fixtureName, '01')) {
                    // These fixtures already had LinkText, which should not have changed.
                    $expectedLinkText = $fixtureName;
                } elseif ($skipMigration === $suffix) {
                    $expectedLinkText = null;
                } else {
                    $expectedLinkText = 'link title #' . $id;
                }
                $versionedTable = $baseTable . $suffix;
                $idColumn = $db->escapeIdentifier($versionedTable . '.ID');
                $select = new SQLSelect('LinkText', [$versionedTable], [$idColumn => $id]);
                $this->assertSame($expectedLinkText, $select->execute()->value());
            }
        }

        // Test whether Title column was dropped
        foreach (['', '_Versions', '_Live'] as $suffix) {
            if ($skipMigration === $suffix || $skipDropColumn === $suffix) {
                $this->assertTrue(
                    in_array('Title', array_keys(DB::field_list($baseTable . $suffix))),
                    'Title column should NOT be removed - suffix: ' . $suffix
                );
            } else {
                $this->assertFalse(
                    in_array('Title', array_keys(DB::field_list($baseTable . $suffix))),
                    'Title column should be removed - suffix: ' . $suffix
                );
            }
        }
    }

    public function provideSetOwnerForHasOneLinks(): array
    {
        return [
            [
                'ownerClass' => LinkOwner::class,
                'fixtureRelationsHaveLink' => [
                    'owns-has-one' => [
                        // Link exists and Owner relation is set to this record
                        'Link' => true,
                    ],
                    'owns-has-one-again' => [
                        // Link exists but Owner relation is NOT set to this record
                        'Link' => false,
                    ],
                    'owns-nothing' => [
                        // Link does not exist
                        'Link' => null,
                    ],
                ],
            ],
            [
                'ownerClass' => MultiLinkOwner::class,
                'fixtureRelationsHaveLink' => [
                    'owns-one' => [
                        'LinkOne' => true,
                        'LinkTwo' => null,
                        'NotLink' => null,
                    ],
                    'owns-another' => [
                        'LinkOne' => null,
                        'LinkTwo' => true,
                        'NotLink' => null,
                    ],
                    'owns-multiple' => [
                        'LinkOne' => true,
                        'LinkTwo' => true,
                        'NotLink' => null,
                    ],
                ],
            ],
            [
                'ownerClass' => ReciprocalLinkOwner::class,
                'fixtureRelationsHaveLink' => [
                    'owns-many' => [
                        'BaseLink' => true,
                        'CustomLink' => true,
                        'BelongsToLink' => false,
                        'HasManyLink' => false,
                    ],
                ],
            ],
            [
                'ownerClass' => PolymorphicLinkOwner::class,
                'fixtureRelationsHaveLink' => [
                    'owns-many' => [
                        'PolymorphicLink' => true,
                        'PolymorphicReciprocalLink' => false,
                        'MultiRelationalLinkOne' => true,
                        'MultiRelationalLinkTwo' => false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideSetOwnerForHasOneLinks
     */
    public function testSetOwnerForHasOneLinks(string $ownerClass, array $fixtureRelationsHaveLink): void
    {
        $this->startCapturingOutput();
        $this->callPrivateMethod('setOwnerForHasOneLinks');
        $output = $this->stopCapturingOutput();

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
                        $isSpecialCase = $ownerClass === LinkOwner::class && $ownerFixture === 'owns-has-one-again';
                        // Relation should be for link, but should not have its Owner set.
                        $this->assertTrue($link->isInDB(), "Relation {$relation} should have a link saved in it");
                        // Can't check OwnerClass here - see https://github.com/silverstripe/silverstripe-framework/issues/11165
                        $this->assertSame(
                            [
                                $isSpecialCase ? $this->idFromFixture(LinkOwner::class, 'owns-has-one') : 0,
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

    public function provideSetOwnerForHasOneLinksSkipClass(): array
    {
        return [
            'skip all DataObjects' => [
                'skipHierarchy' => DataObject::class,
            ],
            'just skip LinkOwner' => [
                'skipHierarchy' => LinkOwner::class,
            ],
        ];
    }

    /**
     * Tests that classes_that_are_not_link_owners skips both the class itself and all its subclasses.
     * @dataProvider provideSetOwnerForHasOneLinksSkipClass
     */
    public function testSetOwnerForHasOneLinksSkipClass(string $skipHierarchy): void
    {
        LinkFieldMigrationTask::config()->merge('classes_that_are_not_link_owners', [$skipHierarchy]);

        $this->startCapturingOutput();
        $this->callPrivateMethod('setOwnerForHasOneLinks');
        $output = $this->stopCapturingOutput();

        $idsToFixtures = [
            $this->idFromFixture(LinkOwner::class, 'owns-has-one') => 'owns-has-one',
            $this->idFromFixture(LinkOwner::class, 'owns-has-one-again') => 'owns-has-one-again',
            $this->idFromFixture(LinkOwner::class, 'owns-nothing') => 'owns-nothing',
        ];

        foreach (LinkOwner::get() as $owner) {
            if (!array_key_exists($owner->ID, $idsToFixtures)) {
                continue;
            }
            $fixture = $idsToFixtures[$owner->ID];
            $link = $owner->Link();
            // Can't check OwnerClass here - see https://github.com/silverstripe/silverstripe-framework/issues/11165
            $this->assertSame(
                [
                    $fixture === 'owns-nothing' ? null : 0,
                    null
                ],
                [
                    $link->OwnerID,
                    $link->OwnerRelation,
                ],
                "Link for fixture {$fixture} should not have its Owner relation set."
            );
        }

        $this->assertSame("Setting owners for has_one relations.\n", $output);
    }

    /**
     * Tests output and result for setOwnerForHasOneLinks when the relation has an ambiguous reciprocal relation
     */
    public function testSetOwnerForHasOneLinksAmbiguous(): void
    {
        $this->startCapturingOutput();
        $this->callPrivateMethod('setOwnerForHasOneLinks');
        $output = $this->stopCapturingOutput();

        $record = $this->objFromFixture(AmbiguousLinkOwner::class, 'owns-one');
        /** @var Link $link */
        $link = $record->Link();

        // Check link exists, but doesn't have its Owner relation set.
        $this->assertTrue($link->isInDB());
        // Can't check OwnerClass here - see https://github.com/silverstripe/silverstripe-framework/issues/11165
        $this->assertSame(
            [
                0,
                null
            ],
            [
                $link->OwnerID,
                $link->OwnerRelation,
            ]
        );

        $linkClass = CustomLink::class;
        $foreignClass = AmbiguousLinkOwner::class;
        $this->assertSame("Setting owners for has_one relations.\nAmbiguous relation '{$linkClass}.AmbiguousOwner' found - assuming it points at '{$foreignClass}.Link'\n", $output);
    }

    public function provideMigrateHasManyRelations(): array
    {
        return [
            'no has_many' => [
                'hasManyConfig' => [],
            ],
            'has_one for has_many still exists' => [
                'hasManyConfig' => [
                    HasManyLinkOwner::class => [
                        'ForHasOne' => [
                            'linkClass' => CustomLink::class,
                            'hasOne' => 'ForHasMany',
                        ],
                    ],
                ],
                'ownerFixture' => 'still-has-relation',
                'addColumns' => [],
                'relationStillExists' => true,
            ],
            'regular has_one' => [
                'hasManyConfig' => [
                    HasManyLinkOwner::class => [
                        'RegularHasMany' => [
                            'linkClass' => Link::class,
                            'hasOne' => 'OldHasOne',
                        ],
                    ],
                ],
                'ownerFixture' => 'legacy-relations',
                'addColumns' => ['OldHasOneID' => DBInt::class],
            ],
            'polymorphic has_one' => [
                'hasManyConfig' => [
                    HasManyLinkOwner::class => [
                        'PolyHasMany' => [
                            'linkClass' => Link::class,
                            'hasOne' => 'OldHasOne',
                        ],
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

    /**
     * @dataProvider provideMigrateHasManyRelations
     */
    public function testMigrateHasManyRelations(
        array $hasManyConfig,
        string $ownerFixture = null,
        array $addColumns = [],
        bool $relationStillExists = false
    ): void {
        LinkFieldMigrationTask::config()->set('has_many_links_data', $hasManyConfig);

        if (!empty($addColumns) && !$ownerFixture) {
            throw new LogicException('Test scenario is broken - need owner if we are adding columns.');
        }

        // Set up legacy has_one columns and data
        if ($ownerFixture) {
            $this->ensureColumnsExist($addColumns);
            $baseTable = DataObject::getSchema()->baseDataTable(Link::class);
            $db = DB::get_conn();
            $ownerClass = array_key_first($hasManyConfig);
            $owner = $this->objFromFixture($ownerClass, $ownerFixture);
            foreach (array_keys($addColumns) as $columnName) {
                $value = str_ends_with($columnName, 'ID') ? $owner->ID : $owner->ClassName;
                SQLUpdate::create(
                    $db->escapeIdentifier($baseTable),
                    [$db->escapeIdentifier("{$baseTable}.{$columnName}") => $value]
                )->execute();
            }
        }

        if ($relationStillExists) {
            $message = '';
            foreach ($hasManyConfig as $ownerClass => $relationData) {
                $owner = $this->objFromFixture($ownerClass, $ownerFixture);
                foreach ($relationData as $hasManyRelation => $spec) {
                    $linkClass = $spec['linkClass'];
                    $hasOneRelation = $spec['hasOne'];
                    $message .= "has_one relation '{$linkClass}.{$hasOneRelation} still exists. Cannot migrate has_many relation '{$ownerClass}.{$hasManyRelation}'.";
                }
            }
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage($message);
        }

        // Run the migration
        $this->startCapturingOutput();
        try {
            $this->callPrivateMethod('migrateHasManyRelations');
        } finally {
            // If $relationStillExists is true, we will have an exception before this is run,
            // but we still need to make sure we stop capturing output!
            $output = $this->stopCapturingOutput();
        }

        if (empty($hasManyConfig)) {
            $this->assertSame("No has_many relations to migrate.\n", $output);
            return;
        }

        $expectedOutput = "Migrating has_many relations.\n";


        if (!$relationStillExists) {
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
        }

        // Make sure we report about dropped columns
        foreach (['', '_Versions', '_Live'] as $suffix) {
            foreach (array_keys($addColumns) as $column) {
                $expectedOutput .= "Dropping '{$baseTable}{$suffix}.{$column}' column.\n";
            }
        }

        $this->assertSame($expectedOutput, $output);
    }

    public function providePublishLinks(): array
    {
        return [
            'skip nothing' => [
                'shouldPublishLink' => true,
                'shouldPublishLinks' => true,
            ],
            'skip all links' => [
                'shouldPublishLink' => true,
                'shouldPublishLinks' => false,
            ],
            'skip individual links' => [
                'shouldPublishLink' => false,
                'shouldPublishLinks' => true,
            ],
        ];
    }

    /**
     * @dataProvider providePublishLinks
     */
    public function testPublishLinks(bool $shouldPublishLink, bool $shouldPublishLinks): void
    {
        // Get the live table before calling publishLinks
        $liveTable = DataObject::getSchema()->tableName(Link::class) . '_Live';
        $liveLinkContents = SQLSelect::create()
            ->setFrom(Convert::symbol2sql($liveTable))
            ->execute()
            ->map();

        OverrideMigrationStepsExtension::$shouldPublishLink = $shouldPublishLink;
        OverrideMigrationStepsExtension::$shouldPublishLinks = $shouldPublishLinks;
        $this->startCapturingOutput();
        $this->callPrivateMethod('publishLinks');
        $output = $this->stopCapturingOutput();

        // Get the live table contents post-publish
        $newLiveLinkContents = SQLSelect::create()
            ->setFrom(Convert::symbol2sql($liveTable))
            ->execute()
            ->map();

        // Output is based on whether we're skiping publishing for all links or not
        if ($shouldPublishLinks) {
            $this->assertSame("Publishing links.\nPublishing complete.\n", $output);
        } else {
            $this->assertSame("Skipping publish step.\n", $output);
        }

        // If we're publishing the links we should have different content in the live table
        if ($shouldPublishLink && $shouldPublishLinks) {
            $this->assertNotSame($liveLinkContents, $newLiveLinkContents);
        } else {
            $this->assertSame($liveLinkContents, $newLiveLinkContents);
        }
    }

    /**
     * Mostly this is just to validate that no errors get thrown if there's nothing to publish
     */
    public function testPublishLinksNoLinks(): void
    {
        Link::get()->removeAll();
        // Get the live table before calling publishLinks
        $liveTable = DataObject::getSchema()->tableName(Link::class) . '_Live';
        $liveLinkContents = SQLSelect::create()
            ->setFrom(Convert::symbol2sql($liveTable))
            ->execute()
            ->map();
        // Call the method
        $this->startCapturingOutput();
        $this->callPrivateMethod('publishLinks');
        // Check output
        $this->assertSame(
            "Publishing links.\nPublishing complete.\n",
            $this->stopCapturingOutput()
        );
        // Check the live table didn't change
        $newLiveLinkContents = SQLSelect::create()
            ->setFrom(Convert::symbol2sql($liveTable))
            ->execute()
            ->map();
        $this->assertSame($liveLinkContents, $newLiveLinkContents);
    }

    /**
     * Test that nothing changes and correct text is output for publish step when links aren't versioned
     */
    public function testPublishLinksUnversioned(): void
    {
        // Get the live table before calling publishLinks
        $liveTable = DataObject::getSchema()->tableName(Link::class) . '_Live';
        $liveLinkContents = SQLSelect::create()
            ->setFrom(Convert::symbol2sql($liveTable))
            ->execute()
            ->map();
        // Remove versioning
        Link::remove_extension(Versioned::class);
        // Call the method
        $this->startCapturingOutput();
        $this->callPrivateMethod('publishLinks');
        // Check output
        $this->assertSame(
            "Links are not versioned - skipping publish step due to project-level customisation.\n",
            $this->stopCapturingOutput()
        );
        // Check the live table didn't change
        $newLiveLinkContents = SQLSelect::create()
            ->setFrom(Convert::symbol2sql($liveTable))
            ->execute()
            ->map();
        $this->assertSame($liveLinkContents, $newLiveLinkContents);
    }

    public function provideCheckForBrokenLinks(): array
    {
        return [
            'no broken links' => [
                'hasBrokenLinks' => false,
            ],
            'with broken links' => [
                'hasBrokenLinks' => true,
            ],
        ];
    }

    /**
     * @dataProvider provideCheckForBrokenLinks
     */
    public function testCheckForBrokenLinks(bool $hasBrokenLinks): void
    {
        $brokenLinkFixtures = $this->getBrokenLinkFixtures($hasBrokenLinks);
        $this->startCapturingOutput();
        $this->callPrivateMethod('checkForBrokenLinks');
        $output = $this->stopCapturingOutput();

        $expectedOutputRegex = "Checking for broken links.\n";
        if ($hasBrokenLinks) {
            foreach (array_keys($brokenLinkFixtures) as $class) {
                $safeClass = preg_quote($class);
                $expectedOutputRegex .= "Found 2 broken links for the '$safeClass' class.\n";
            }
            $classNameLen = strlen(CustomLink::class);
            $spaces = str_repeat(' ', $classNameLen - strlen('Link class'));
            $hyphens = str_repeat('-', $classNameLen);
            $expectedOutputRegex .= <<< CLI_TABLE
            Broken links:
            Link class$spaces | IDs of broken links
            $hyphens | -------------------\n
            CLI_TABLE;
            foreach ($brokenLinkFixtures as $class => $ids) {
                $paddedClass = str_pad($class, $classNameLen);
                $safeClass = preg_quote($paddedClass);
                // ID order isn't reliable, so we'll just check the format here and check the actual values separately.
                $idsPlaceholder = str_repeat('\d+, ', count($ids) - 1) . '\d+';
                $expectedOutputRegex .= "$safeClass | $idsPlaceholder\n";
            }
        } else {
            foreach (array_keys($brokenLinkFixtures) as $class) {
                $safeClass = preg_quote($class);
                $expectedOutputRegex .= "Found 0 broken links for the '$safeClass' class.\n";
            }
            $expectedOutputRegex .= "No broken links.\n";
        }
        $this->assertMatchesRegularExpression('#' . $expectedOutputRegex . '#', $output);

        // Check the IDs are correct (regardless of order)
        if ($hasBrokenLinks) {
            $output = preg_replace('/.*-------------------\n/s', '', $output);
            $rows = explode("\n", $output);
            foreach ($rows as $row) {
                if (empty(trim($row))) {
                    continue;
                }
                $parts = explode('|', $row);
                if (count($parts) !== 2) {
                    echo '';
                }
                $class = trim($parts[0]);
                $ids = explode(', ', trim($parts[1]));
                $toMatch = $brokenLinkFixtures[$class];
                sort($ids);
                sort($toMatch);
                $this->assertEquals($toMatch, $ids);
            }
        }
    }

    /**
     * @dataProvider provideCheckForBrokenLinks
     */
    public function testCheckForBrokenLinksWithHtmlOutput(bool $hasBrokenLinks): void
    {
        // Make sure we get HTML output
        $reflectionCli = new ReflectionClass(Environment::class);
        $reflectionCli->setStaticPropertyValue('isCliOverride', false);
        try {
            $brokenLinkFixtures = $this->getBrokenLinkFixtures($hasBrokenLinks);
            $this->startCapturingOutput();
            $this->callPrivateMethod('checkForBrokenLinks');
            $output = $this->stopCapturingOutput();

            $expectedOutputRegex = '#Checking for broken links\.<br>';
            if ($hasBrokenLinks) {
                foreach (array_keys($brokenLinkFixtures) as $class) {
                    $safeClass = preg_quote($class);
                    $expectedOutputRegex .= "Found 2 broken links for the '$safeClass' class\.<br>";
                }
                $expectedOutputRegex .= 'Broken links:<br><table><thead><tr><th>Link class</th><th>IDs of broken links</th></tr></thead><tbody>';
                foreach ($brokenLinkFixtures as $class => $ids) {
                    $safeClass = preg_quote($class);
                    // ID order isn't reliable, so we'll just check the format here and check the actual values separately.
                    $idsPlaceholder = str_repeat('\d+, ', count($ids) - 1) . '\d+';
                    $expectedOutputRegex .= "<tr><td>$safeClass</td><td>$idsPlaceholder</td></tr>";
                }
                $expectedOutputRegex .= '</tbody></table><br>#';
            } else {
                foreach (array_keys($brokenLinkFixtures) as $class) {
                    $safeClass = preg_quote($class);
                    $expectedOutputRegex .= "Found 0 broken links for the '$safeClass' class\.<br>";
                }
                $expectedOutputRegex .= 'No broken links\.<br>#';
            }
            $this->assertMatchesRegularExpression($expectedOutputRegex, $output);

            // Check the IDs are correct (regardless of order)
            if ($hasBrokenLinks) {
                $crawler = new Crawler($output);
                $tableRows = $crawler->filter('table tbody tr');
                foreach ($tableRows as $row) {
                    $class = trim($row->firstChild->textContent);
                    $ids = explode(', ', trim($row->lastChild->textContent));
                    $toMatch = $brokenLinkFixtures[$class];
                    sort($ids);
                    sort($toMatch);
                    $this->assertEquals($toMatch, $ids);
                }
            }
        } finally {
            // Make sure we unset the CLI override
            $reflectionCli->setStaticPropertyValue('isCliOverride', null);
        }
    }

    private function getBrokenLinkFixtures(bool $hasBrokenLinks): array
    {
        $fixtures = [
            EmailLink::class => [
                $this->idFromFixture(EmailLink::class, 'broken-email-link01'),
                $this->idFromFixture(EmailLink::class, 'broken-email-link02'),
            ],
            ExternalLink::class => [
                $this->idFromFixture(ExternalLink::class, 'broken-external-link01'),
                $this->idFromFixture(ExternalLink::class, 'broken-external-link02'),
            ],
            FileLink::class => [
                $this->idFromFixture(FileLink::class, 'broken-file-link01'),
                $this->idFromFixture(FileLink::class, 'broken-file-link02'),
            ],
            PhoneLink::class => [
                $this->idFromFixture(PhoneLink::class, 'broken-phone-link01'),
                $this->idFromFixture(PhoneLink::class, 'broken-phone-link02'),
            ],
            SiteTreeLink::class => [
                $this->idFromFixture(SiteTreeLink::class, 'broken-sitetree-link01'),
                $this->idFromFixture(SiteTreeLink::class, 'broken-sitetree-link02'),
            ],
            CustomLink::class => [
                $this->idFromFixture(CustomLink::class, 'broken-custom-link01'),
                $this->idFromFixture(CustomLink::class, 'broken-custom-link02'),
            ],
        ];
        if (!$hasBrokenLinks) {
            foreach ($fixtures as $class => $ids) {
                $list = new DataList($class);
                $list->byIDs($ids)->removeAll();
            }
        }
        return $fixtures;
    }

    private function startCapturingOutput(): void
    {
        flush();
        ob_start();
    }

    private function stopCapturingOutput(): string
    {
        return ob_get_clean();
    }

    private function callPrivateMethod(string $methodName, array $args = []): mixed
    {
        $task = new LinkFieldMigrationTask();
        $reflectionMethod = new ReflectionMethod($task, $methodName);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invoke($task, ...$args);
    }

    private function ensureColumnsExist(array $columns, bool $includeVersioned = true)
    {
        $baseTable = DataObject::getSchema()->baseDataTable(Link::class);
        $tables = [$baseTable];
        if ($includeVersioned) {
            $tables[] = "{$baseTable}_Versions";
            $tables[] = "{$baseTable}_Live";
        }
        DB::get_schema()->schemaUpdate(function () use ($tables, $columns) {
            foreach ($tables as $table) {
                foreach ($columns as $column => $fieldType) {
                    $dbField = DBField::create_field($fieldType, null, $column);
                    $dbField->setTable($table);
                    $dbField->requireField();
                }
            }
        });
    }
}
