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
use SilverStripe\LinkField\Tests\Tasks\LinkableMigrationTaskTest\CustomLinkableLink;
use SilverStripe\LinkField\Tests\Tasks\LinkableMigrationTaskTest\HasManyLinkableLinkOwner;
use SilverStripe\LinkField\Tests\Tasks\LinkableMigrationTaskTest\HasOneLinkableLinkOwner;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Versioned\Versioned;

class LinkableMigrationTaskTest extends SapphireTest
{
    protected $baseTable;

    protected $oldTable;

    protected static $fixture_file = 'LinkableMigrationTaskTest.yml';

    protected static $extra_dataobjects = [
        CustomLinkableLink::class,
        HasManyLinkableLinkOwner::class,
        HasOneLinkableLinkOwner::class,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseTable = DataObject::getSchema()->baseDataTable(Link::class);
        $this->oldTable = DataObject::getSchema()->baseDataTable(CustomLinkableLink::class);
        LinkableMigrationTask::config()->set('old_link_table', $this->oldTable);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testGetNeedsMigration()
    {
        $result = $this->callPrivateMethod('getNeedsMigration');
        $this->assertTrue($result);
    }

    public function testInsertBaseRows()
    {
        $this->callPrivateMethod('insertBaseRows');

        $db = DB::get_conn();
        
        $baseRecords = new SQLSelect('*', $db->escapeIdentifier($this->baseTable));
        $result = $baseRecords->execute();
        
        foreach ($result as $link) {
            $oldRecord = new SQLSelect('*', $db->escapeIdentifier($this->oldTable), ['ID' => $link['ID']]);
            $oldRecord = $oldRecord->execute()->record();

            $this->assertSame($oldRecord['ID'], $link['ID']);
            $this->assertSame($oldRecord['Title'], $link['LinkText']);
            $this->assertSame($oldRecord['OpenInNewWindow'], $link['OpenInNew']);
        }

        $this->assertFalse(empty($result));
    }

    public function testInsertTypeSpecificRows()
    {
        $this->callPrivateMethod('insertTypeSpecificRows');

        $db = DB::get_conn();
        
        $baseRecords = new SQLSelect('*', $db->escapeIdentifier($this->baseTable));
        $result = $baseRecords->execute();
        foreach ($result as $link) {
            $oldRecord = new SQLSelect('*', $db->escapeIdentifier($this->oldTable), ['ID' => $link['ID']]);
            $oldRecord = $oldRecord->execute()->record();

            $siteTreeLinkRecord = new SQLSelect('*', $db->escapeIdentifier(SiteTreeLink::class), ['ID' => $link['ID']]);

            $this->assertSame($oldRecord['ID'], $link['ID']);
            $this->assertSame($oldRecord['Title'], $link['LinkText']);
            $this->assertSame($oldRecord['OpenInNewWindow'], $link['OpenInNew']);
        }

        $this->assertFalse(empty($result));
    }

    public function testMigrateHasManyRelations()
    {
        LinkableMigrationTask::config()->set(
            'has_many_links_data',
            [
                HasManyLinkableLinkOwner::class => [
                    'HasManyLinks' => [
                        'linkClass' => CustomLinkableLink::class,
                        'hasOne' => 'ForHasMany',
                    ],
                ],
            ],
        );

        $this->callPrivateMethod('migrateHasManyRelations');

        $db = DB::get_conn();
        
        $baseRecords = new SQLSelect('*', $db->escapeIdentifier($this->baseTable));
        $result = $baseRecords->execute();
        foreach ($result as $link) {
            $oldRecord = new SQLSelect('*', $db->escapeIdentifier($this->oldTable), ['ID' => $link['ID']]);
            $oldRecord = $oldRecord->execute()->record();

            $siteTreeLinkRecord = new SQLSelect('*', $db->escapeIdentifier(SiteTreeLink::class), ['ID' => $link['ID']]);

            $this->assertSame($oldRecord['OwnerID'], $link['OwnerID']);
            $this->assertSame($oldRecord['OwnerClass'], $link['OwnerClass']);
            $this->assertSame($oldRecord['OwnerRelation'], $link['OwnerRelation']);
        }

        $this->assertFalse(empty($result));
    }

    private function callPrivateMethod(string $methodName, array $args = []): mixed
    {
        $task = Deprecation::withNoReplacement(fn() => new LinkableMigrationTask());
        $reflectionMethod = new ReflectionMethod($task, $methodName);
        $reflectionMethod->setAccessible(true);
        return $reflectionMethod->invoke($task, ...$args);
    }
}
