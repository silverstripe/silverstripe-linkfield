<?php

namespace SilverStripe\LinkField\Tests\Traits;

use ArrayIterator;
use InvalidArgumentException;
use ReflectionMethod;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Form\Traits\AllowedLinkClassesTrait;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\LinkField\Tests\Controllers\LinkFieldControllerTest\TestPhoneLink;
use SilverStripe\ORM\DataObject;

class AllowedLinkClassesTraitTest extends SapphireTest
{
    /**
     * Set of Link subclasses to test.
     * Need to include only known Link subclasses.
     */
    private $link_types = [
        SiteTreeLink::class,
        ExternalLink::class,
        FileLink::class,
        EmailLink::class,
        PhoneLink::class,
        TestPhoneLink::class,
    ];

    /**
     * @dataProvider allowedTypesDataProvider
     */
    public function testSetAllowedTypes(array $enabled, array $expected)
    {
        $trait = LinkField::create('LinkField');
        $trait->setAllowedTypes($enabled);
        // Get all unknown Link types
        $diff = array_diff($trait->getAllowedTypes(), $this->link_types);
        // Leave only known Link subclasses
        $result = array_diff($trait->getAllowedTypes(), $diff);
        $this->assertEquals($expected, $result);
    }

    public function allowedTypesDataProvider() : array
    {
        return [
            'allow all Link classes' => [
                'enabled' => [
                  SiteTreeLink::class,
                  ExternalLink::class,
                  FileLink::class,
                  EmailLink::class,
                  PhoneLink::class,
                  TestPhoneLink::class,
                ],
                'expected' => [
                  SiteTreeLink::class,
                  ExternalLink::class,
                  FileLink::class,
                  EmailLink::class,
                  PhoneLink::class,
                  TestPhoneLink::class,
                ],
            ],
            'allow only SiteTreeLink class' => [
                'enabled' => [SiteTreeLink::class],
                'expected' => [SiteTreeLink::class],
            ],
        ];
    }

    /**
     * @dataProvider allowedTypesExceptionDataProvider
     */
    public function testSetAllowedTypesException(array $enabled)
    {
        $trait = LinkField::create('LinkField');
        $this->expectException(InvalidArgumentException::class);
        $trait->setAllowedTypes($enabled);
    }

    public function allowedTypesExceptionDataProvider() : array
    {
        return [
            'allow all with empty array' => [
                'enabled' => [],
            ],
            'all all non-Link classes' => [
                'enabled' => [DataObject::class, 'WrongClass', 1, true],
            ],
            'allow one PhoneLink and few non-Link classes' => [
                'enabled' => [PhoneLink::class, 'WrongClass', 1, true],
            ],
        ];
    }
}
