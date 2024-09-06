<?php

namespace SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

class ReciprocalLinkOwner extends DataObject implements TestOnly
{
    private static string $table_name = 'LinkFieldTest_Tasks_ReciprocalLinkOwner';

    private static array $has_one = [
        'BaseLink' => Link::class,
        'CustomLink' => CustomLink::class,
        'BelongsToLink' => CustomLink::class,
        'HasManyLink' => CustomLink::class,
    ];
}
