<?php

namespace SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\LinkField\Models\Link;

class WasHasOneLinkOwner extends DataObject implements TestOnly
{
    private static string $table_name = 'LinkFieldTest_Tasks_WasHasOneLinkableLinkOwner';

    private static array $has_one = [
        'Link' => Link::class,
    ];
}
