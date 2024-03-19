<?php

namespace SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

class MultiLinkOwner extends DataObject implements TestOnly
{
    private static string $table_name = 'LinkFieldTest_Tasks_MultiLinkOwner';

    private static array $has_one = [
        'LinkOne' => Link::class,
        'LinkTwo' => Link::class,
        'NotLink' => SiteTree::class,
    ];
}
