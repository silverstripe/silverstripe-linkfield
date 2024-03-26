<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tests\Tasks\LinkableMigrationTaskTest;

use SilverStripe\Dev\TestOnly;
use Sheadawson\Linkable\Models\Link;

class CustomLinkableLink extends Link implements TestOnly
{
    private static string $table_name = 'Linkable_Test_Custom_Link';

    private static array $has_one = [
        'ForHasMany' => HasManyLinkableLinkOwner::class,
    ];
}
