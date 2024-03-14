<?php

namespace SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

class WasManyManyJoinModel extends DataObject implements TestOnly
{
    private static string $table_name = 'LinkFieldTest_Tasks_WasManyManyJoinModel';

    private static array $has_one = [
        'Owner' => WasManyManyOwner::class,
        'Link' => Link::class,
    ];
}
