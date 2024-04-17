<?php

namespace SilverStripe\LinkField\Tests\Tasks\LinkableMigrationTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\LinkField\Models\Link;

class HasManyLinkableLinkOwner extends DataObject implements TestOnly
{
    private static string $table_name = 'Linkable_Test_HasManyLinkableLinkOwner';

    private static array $has_many = [
        'HasManyLinks' => Link::class,
    ];
}
