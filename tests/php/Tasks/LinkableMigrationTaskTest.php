<?php

namespace SilverStripe\LinkField\Tests\Tasks;

use LogicException;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use SilverStripe\Dev\Deprecation;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\LinkField\Tasks\LinkableMigrationTask;
use SilverStripe\LinkField\Tests\Tasks\LinkableMigrationTaskTest\HasManyLinkableLinkOwner;
use SilverStripe\LinkField\Tests\Tasks\LinkableMigrationTaskTest\HasOneLinkableLinkOwner;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Versioned\Versioned;

class LinkableMigrationTaskTest extends SapphireTest
{
    private const OLD_LINK_TABLE = 'LinkableMigrationTaskTest_OldLinkTable';

    private const TYPE_MAP = [
        'URL' => ExternalLink::class,
        'Email' => EmailLink::class,
        'Phone' => PhoneLink::class,
        'File' => FileLink::class,
        'SiteTree' => SiteTreeLink::class,
    ];

    protected static $fixture_file = 'LinkableMigrationTaskTest.yml';

    protected static $extra_dataobjects = [
        HasManyLinkableLinkOwner::class,
        HasOneLinkableLinkOwner::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        LinkableMigrationTask::config()->merge('base_link_columns', [
            'MySort' => 'Sort',
        ]);
    }

    public function onBeforeLoadFixtures(): void
    {
        LinkableMigrationTask::config()->set('old_link_table', self::OLD_LINK_TABLE);
        // Set up migration tables
        DB::get_schema()->schemaUpdate(function () {
            // Old link table
            $linkDbColumns = [
                ...DataObject::config()->uninherited('fixed_fields'),
                // Fields directly from the Link class
                'Title' => 'Varchar(255)',
                'Type' => 'Varchar',
                'URL' => 'Varchar(255)',
                'Email' => 'Varchar(255)',
                'Phone' => 'Varchar(255)',
                'OpenInNewWindow' => 'Boolean',
                'FileID' => 'ForeignKey',
                // Fields from the LinkableSiteTreeExtension
                'Anchor' => 'Varchar(255)',
                'SiteTreeID' => 'ForeignKey',
                // Field for custom fixture
                'MySort' => 'Int',
            ];
            DB::require_table(self::OLD_LINK_TABLE, $linkDbColumns, options: DataObject::config()->get('create_table_options'));
        });
        parent::onBeforeLoadFixtures();
    }

    public function testInsertBaseRows(): void
    {
        // Remove existing links which can cause ID conflicts.
        // Note they would have already caused the migration to abort before this point.
        Link::get()->removeAll();

        // Insert the rows
        $this->startCapturingOutput();
        $this->callPrivateMethod('insertBaseRows');
        $output = $this->stopCapturingOutput();

        $select = new SQLSelect(from: DB::get_conn()->escapeIdentifier(DataObject::getSchema()->baseDataTable(Link::class)));
        foreach ($select->execute() as $link) {
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
        $this->startCapturingOutput();
        $this->callPrivateMethod('insertBaseRows');
        $this->stopCapturingOutput();

        // Insert the rows
        $this->startCapturingOutput();
        $this->callPrivateMethod('insertTypeSpecificRows');
        $output = $this->stopCapturingOutput();

        $oldLinkSelect = new SQLSelect(from: DB::get_conn()->escapeIdentifier(self::OLD_LINK_TABLE));
        $oldLinkData = $oldLinkSelect->execute();
        $this->assertCount($oldLinkData->numRecords(), Link::get());

        $typeColumnMaps = LinkableMigrationTask::config()->get('link_type_columns');
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

    public function testSetOwnerForHasOneLinks(): void
    {
        // Remove existing links which can cause ID conflicts.
        // Note they would have already caused the migration to abort before this point.
        Link::get()->removeAll();
        // This test is dependent on the base rows being inserted
        $this->startCapturingOutput();
        $this->callPrivateMethod('insertBaseRows');
        $this->stopCapturingOutput();
        // Insert the has_one Owner's rows
        $this->startCapturingOutput();
        $this->callPrivateMethod('setOwnerForHasOneLinks');
        $output = $this->stopCapturingOutput();

        $ownerClass = HasOneLinkableLinkOwner::class;
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
                        $isSpecialCase = $ownerClass === HasOneLinkableLinkOwner::class && $ownerFixture === 'owns-has-one-again';
                        // Relation should be for link, but should not have its Owner set.
                        $this->assertTrue($link->isInDB(), "Relation {$relation} should have a link saved in it");
                        // Can't check OwnerClass here - see https://github.com/silverstripe/silverstripe-framework/issues/11165
                        $this->assertSame(
                            [
                                $isSpecialCase ? $this->idFromFixture(HasOneLinkableLinkOwner::class, 'owns-has-one') : 0,
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
        $task = new LinkableMigrationTask();
        $reflectionProperty = new ReflectionProperty($task, 'oldTableName');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($task, self::OLD_LINK_TABLE);
        $reflectionMethod = new ReflectionMethod($task, $methodName);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invoke($task, ...$args);
    }
}
