<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\LinkField\Models\Link;

class CustomLink extends Link implements TestOnly
{
    private static string $table_name = 'LinkFieldTest_Tasks_CustomLink';

    private static array $db = [
        'MyField' => 'Varchar',
    ];

    private static array $has_one = [
        'ForHasMany' => HasManyLinkOwner::class,
    ];

    private static array $belongs_to = [
        'ReciprocalLinkOwner' => ReciprocalLinkOwner::class . '.BelongsToLink',
    ];

    private static array $has_many = [
        'AmbiguousOwner' => AmbiguousLinkOwner::class,
        'ReciprocalLinkOwners' => ReciprocalLinkOwner::class . '.HasManyLink',
        'PolymorphicLinkOwners' => PolymorphicLinkOwner::class . '.PolymorphicReciprocalLink',
    ];
}
