<?php

namespace SilverStripe\LinkField\Tests\Traits;

use ArrayIterator;
use InvalidArgumentException;
use ReflectionMethod;
use SilverStripe\Core\Injector\Injector;
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
        'sitetree' => SiteTreeLink::class,
        'external' => ExternalLink::class,
        'file' => FileLink::class,
        'email' => EmailLink::class,
        'phone' => PhoneLink::class,
        'testphone' => TestPhoneLink::class,
    ];

    public function setUp(): void
    {
        parent::setUp();
        TestPhoneLink::$fail = '';
    }

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
     * @dataProvider provideTypesExceptionDataProvider
     */
    public function testSetAllowedTypesException(array $enabled)
    {
        $trait = LinkField::create('LinkField');
        $this->expectException(InvalidArgumentException::class);
        $trait->setAllowedTypes($enabled);
    }

    public function provideTypesExceptionDataProvider() : array
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

    public function sortedTypesDataProvider() : array
    {
        return [
            'sort all allowed Link classes' => [
                'enabled' => [
                    SiteTreeLink::class,
                    ExternalLink::class,
                    FileLink::class,
                    EmailLink::class,
                    PhoneLink::class,
                    TestPhoneLink::class,
                ],
                'expected' => [
                    'sitetree',
                    'file',
                    'external',
                    'email',
                    'phone',
                    'testphone',
                ],
                'reorder' => false,
            ],
            'sort all allowed Link classes and move TestPhoneLink up ' => [
                'enabled' => [
                    SiteTreeLink::class,
                    ExternalLink::class,
                    FileLink::class,
                    EmailLink::class,
                    PhoneLink::class,
                    TestPhoneLink::class,
                ],
                'expected' => [
                    'sitetree',
                    'testphone',
                    'file',
                    'external',
                    'email',
                    'phone',
                ],
                'reorder' => true,
            ],
            'sort only particular allowed Link class and move TestPhoneLink up' => [
                'enabled' => [
                    SiteTreeLink::class,
                    TestPhoneLink::class,
                    EmailLink::class,
                ],
                'expected' => [
                    'sitetree',
                    'testphone',
                    'file',
                    'external',
                    'email',
                    'phone',
                ],
                'reorder' => true,
            ],
        ];
    }

    /**
     * @dataProvider sortedTypesDataProvider
     */
    public function testGetSortedTypeProps(array $enabled, array $expected, bool $reorder): void
    {
        if ($reorder) {
            Injector::inst()->get(TestPhoneLink::class)->config()->set('menu_priority', 5);
        }

        $linkField = LinkField::create('LinkField');
        $linkField->setAllowedTypes($enabled);
        $json = json_decode($linkField->getTypesProps(), true);
        $json = $this->removeCustomLinkTypes($json);
        $this->assertEquals(array_keys($json), $expected);
    }

    public function testGetTypesPropsCanCreate(): void
    {
        $linkField = LinkField::create('LinkField');
        $linkField->setAllowedTypes([SiteTreeLink::class, TestPhoneLink::class]);
        $json = json_decode($linkField->getTypesProps(), true);
        $this->assertTrue(array_key_exists('sitetree', $json));
        $this->assertTrue(array_key_exists('testphone', $json));
        $this->assertTrue($json['sitetree']['allowed']);
        $this->assertTrue($json['testphone']['allowed']);
        TestPhoneLink::$fail = 'can-create';
        $json = json_decode($linkField->getTypesProps(), true);
        $this->assertTrue(array_key_exists('sitetree', $json));
        $this->assertTrue(array_key_exists('testphone', $json));
        $this->assertTrue($json['sitetree']['allowed']);
        $this->assertFalse($json['testphone']['allowed']);
    }

    public function provideGetTypesProps() : array
    {
        return [
            'SiteTreeLink props' => [
                'class' => SiteTreeLink::class,
                'key' => 'sitetree',
                'title' => 'Page on this site',
                'priority' => 0,
                'icon' => 'font-icon-page',
                'allowed' => true,
            ],
            'EmailLink props' => [
                'class' => EmailLink::class,
                'key' => 'email',
                'title' => 'Link to email address',
                'priority' => 30,
                'icon' => 'font-icon-p-mail',
                'allowed' => false,
            ],
            'ExternalLink props' => [
                'class' => ExternalLink::class,
                'key' => 'external',
                'title' => 'Link to external URL',
                'priority' => 20,
                'icon' => 'font-icon-external-link',
                'allowed' => false,
            ],
            'FileLink props' => [
                'class' => FileLink::class,
                'key' => 'file',
                'title' => 'Link to a file',
                'priority' => 10,
                'icon' => 'font-icon-image',
                'allowed' => true,
            ],
            'PhoneLink props' => [
                'class' => PhoneLink::class,
                'key' => 'phone',
                'title' => 'Phone number',
                'priority' => 40,
                'icon' => 'font-icon-mobile',
                'allowed' => true,
            ],
            'TestPhoneLink props' => [
                'class' => TestPhoneLink::class,
                'key' => 'testphone',
                'title' => 'Test Phone Link',
                'priority' => 100,
                'icon' => 'font-icon-link',
                'allowed' => false,
            ],
        ];
    }

    /**
     * @dataProvider provideGetTypesProps
     */
    public function testGetTypesProps(
        string $class,
        string $key,
        string $title,
        int $priority,
        string $icon,
        bool $allowed
    ): void {
        $linkField = LinkField::create('LinkField');
        if ($allowed) {
            $linkField->setAllowedTypes([$class]);
        } else {
            $diff = array_diff($this->link_types, [$class]);
            $linkField->setAllowedTypes($diff);
        }
        $json = json_decode($linkField->getTypesProps(), true);
        $this->assertEquals($key, $json[$key]['key']);
        $this->assertEquals($title, $json[$key]['title']);
        $this->assertEquals($priority, $json[$key]['priority']);
        $this->assertEquals($icon, $json[$key]['icon']);
        $this->assertEquals($allowed, $json[$key]['allowed']);
    }

    /**
     * Remove any classes defined at the project level that interfere with running unit-tests locally
     */
    private function removeCustomLinkTypes(array $json): array
    {
        $newJson = [];
        foreach ($json as $key => $value) {
            if (array_key_exists($key, $this->link_types)) {
                $newJson[$key] = $value;
            }
        }
        return $newJson;
    }
}
