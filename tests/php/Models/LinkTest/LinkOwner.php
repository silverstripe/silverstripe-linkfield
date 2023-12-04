<?php

namespace SilverStripe\LinkField\Tests\Models\LinkTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

class LinkOwner extends DataObject implements TestOnly
{
    private static array $has_one = [
        'Link' => Link::class,
    ];

    private static array $has_many = [
        'LinkList' => Link::class . '.Owner',
    ];
}
