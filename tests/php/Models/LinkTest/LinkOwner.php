<?php

namespace SilverStripe\LinkField\Tests\Models\LinkTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class LinkOwner extends DataObject implements TestOnly
{
    private static string $table_name = 'LinkFieldTest_Models_LinkOwner';

    private static array $has_one = [
        'Link' => Link::class,
    ];

    private static array $has_many = [
        'LinkList' => Link::class . '.Owner',
        'LinkList2' => Link::class . '.Owner',
    ];

    private static array $extensions = [
        Versioned::class,
    ];

    // Allows us to toggle permissions easily within a unit test
    public bool $canView = true;
    public bool $canEdit = true;

    public function canView($member = null)
    {
        return $this->canView;
    }

    public function canEdit($member = null)
    {
        return $this->canEdit;
    }
}
