<?php

namespace SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class AmbiguousLinkOwner extends DataObject implements TestOnly
{
    private static string $table_name = 'LinkFieldTest_Tasks_AmbiguousLinkOwner';

    private static array $has_one = [
        'Link' => CustomLink::class,
    ];
}
