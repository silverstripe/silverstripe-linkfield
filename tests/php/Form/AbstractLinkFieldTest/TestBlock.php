<?php

namespace SilverStripe\LinkField\Tests\Form\AbstractLinkFieldTest;

use SilverStripe\LinkField\Models\Link;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Dev\TestOnly;

class TestBlock extends BaseElement implements TestOnly
{
    private static $table_name = 'AbstractLinkFieldTest_TestBlock';

    private static $has_one = [
        'MyLink' => Link::class,
    ];

    private static $has_many = [
        'MyLinks' => Link::class,
    ];
}
