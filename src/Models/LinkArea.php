<?php

namespace SilverStripe\LinkField\Models;

use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Extensions\LinkObjectExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

class LinkArea extends DataObject
{
    private static $table_name = 'LinkField_LinkArea';

    private static array $has_many = [
        'Links' => Link::class,
    ];

    private static array $owns = [
        'Links',
    ];

    private static array $cascade_deletes = [
        'Links',
    ];

    private static array $cascade_duplicates = [
        'Links',
    ];

    private static array $extensions = [
        Versioned::class,
        LinkObjectExtension::class,
    ];
}
