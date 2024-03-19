<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Tests\Tasks\LinkFieldMigrationTaskTest;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;

class CustomLinkMigrationExtension extends Extension implements TestOnly
{
    protected function updateCheckForBrokenLinks(array &$toCheck): void
    {
        $toCheck[CustomLink::class] = [
            'field' => 'MyField',
            'emptyValue' => [null, 'broken'],
        ];
    }
}
