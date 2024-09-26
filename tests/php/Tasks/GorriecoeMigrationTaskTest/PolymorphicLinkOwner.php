<?php

namespace SilverStripe\LinkField\Tests\Tasks\GorriecoeMigrationTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;

class PolymorphicLinkOwner extends DataObject implements TestOnly
{
    private static string $table_name = 'LinkFieldTest_Tasks_PolymorphicLinkOwner';

    private static array $has_one = [
        'PolymorphicLink' => DataObject::class,
        'PolymorphicReciprocalLink' => DataObject::class,
        'MultiRelationalLinkOne' => [
            'class' => DataObject::class,
            DataObjectSchema::HAS_ONE_MULTI_RELATIONAL => true,
        ],
        'MultiRelationalLinkTwo' => [
            'class' => DataObject::class,
            DataObjectSchema::HAS_ONE_MULTI_RELATIONAL => true,
        ],
    ];
}
