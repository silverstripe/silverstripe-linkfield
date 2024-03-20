<?php

namespace SilverStripe\LinkField\Tests\Tasks\LinkableMigrationTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\LinkField\Models\Link;

class HasOneLinkableLinkOwner extends DataObject implements TestOnly
{
    private static string $table_name = 'Linkable_Test_HasOneLinkableLinkOwner';

    private static array $has_one = [
        'Link' => Link::class,
    ];
}
