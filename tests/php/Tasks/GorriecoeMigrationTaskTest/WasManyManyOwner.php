<?php

namespace SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

class WasManyManyOwner extends DataObject implements TestOnly
{
    private static string $table_name = 'LinkFieldTest_Tasks_WasManyManyOwner';

    private static array $db = [
        'Title' => 'Varchar',
    ];

    private static array $has_many = [
        'NormalManyMany' => Link::class . '.Owner',
        'ManyManyThrough' => Link::class . '.Owner',
        'ManyManyThroughPolymorphic' => Link::class . '.Owner',
        // These two are here just as a sanity check that additional relationships don't affect the task
        'LinkButNotIncluded' => Link::class . '.Owner',
        'NotLink' => SiteTree::class,
    ];
}
