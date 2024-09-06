<?php

namespace SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

class HasManyLinkOwner extends DataObject implements TestOnly
{
    private static string $table_name = 'LinkFieldTest_Tasks_HasManyLinkOwner';

    private static array $has_many = [
        // Intentionally not using dot notation here.
        'ForHasOne' => CustomLink::class,
        'RegularHasMany' => Link::class,
        'PolyHasMany' => Link::class,
    ];
}
