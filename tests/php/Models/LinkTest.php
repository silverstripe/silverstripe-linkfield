<?php

namespace SilverStripe\LinkField\Tests\Models;

use ReflectionException;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\Image;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Config\Collections\MutableConfigCollectionInterface;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\LinkField\Type\Registry;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Versioned\Versioned;
use SilverStripe\LinkField\Tests\Extensions\ExternalLinkExtension;

class LinkTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = 'LinkTest.yml';

    protected static $required_extensions = [
        ExternalLink::class => [
            ExternalLinkExtension::class,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        TestAssetStore::activate('ImageTest');

        /** @var Image $image */
        $image = $this->objFromFixture(Image::class, 'image-1');
        $image->setFromLocalFile(dirname(dirname(dirname(__FILE__))) . '/resources/600x400.png');
        $image->write();
        $image->publishSingle();

        /** @var SiteTree $page */
        $page = $this->objFromFixture(SiteTree::class, 'page-1');
        $page->publishSingle();
    }

    protected function tearDown(): void
    {
        TestAssetStore::reset();

        parent::tearDown();
    }

    public function testLinkModel(): void
    {
        $model = $this->objFromFixture(Link::class, 'link-1');

        $this->assertEquals('FormBuilderModal', $model->LinkTypeHandlerName());
    }

    /**
     * @throws ValidationException
     */
    public function testSiteTreeLinkTitleFallback(): void
    {
        /** @var SiteTreeLink $model */
        $model = $this->objFromFixture(SiteTreeLink::class, 'page-link-1');

        $this->assertEquals('PageLink1', $model->Title, 'We expect to get a default Link title');

        /** @var SiteTree $page */
        $page = $this->objFromFixture(SiteTree::class, 'page-1');

        $model->PageID = $page->ID;
        $model->Title = null;
        $model->write();

        // The actual Database Title field should still be null
        $this->assertNull($model->getField('Title'));
        $this->assertEquals(null, $model->Title, 'We expect that link does not have a title');

        $customTitle = 'My custom title';
        $model->Title = $customTitle;
        $model->write();

        $this->assertEquals($customTitle, $model->Title, 'We expect to get the custom title not page title');
    }

    /**
     * @param string $class
     * @param bool $expected
     * @throws ReflectionException
     * @dataProvider linkTypeProvider
     */
    public function testLinkType(string $class, bool $expected): void
    {
        /** @var Link $model */
        $model = DataObject::singleton($class);
        $fields = $model->getCMSFields();
        $linkTypeField = $fields->fieldByName('Root.Main.LinkType');
        $expected
            ? $this->assertNotNull($linkTypeField, 'We expect to a find link type field')
            : $this->assertNull($linkTypeField, 'We do not expect to a find link type field');
    }

    public function linkTypeProvider(): array
    {
        return [
            [EmailLink::class, false],
            [ExternalLink::class, false],
            [FileLink::class, false],
            [PhoneLink::class, false],
            [SiteTreeLink::class, false],
            [Link::class, true],
        ];
    }

    /**
     * @param array $types
     * @param array $expected
     * @return void
     * @dataProvider linkTypeEnabledProvider
     */
    public function testLinkTypeEnabled(array $types, array $expected): void
    {
        Config::withConfig(function (MutableConfigCollectionInterface $config) use ($types, $expected): void {
            $config->set(Registry::class, 'types', $types);

            $enabledTypes = Registry::singleton()->list();
            $enabledTypes = array_map(static function (Link $link): string {
                return $link->LinkTypeTile();
            }, $enabledTypes);
            $enabledTypes = array_values($enabledTypes);
            sort($enabledTypes, SORT_STRING);

            $this->assertSame($expected, $enabledTypes, 'We expect specific enabled link types');
        });
    }

    public function linkTypeEnabledProvider(): array
    {
        return [
            'all types enabled' => [
                [
                    'cms' => [
                        'classname' => SiteTreeLink::class,
                        'enabled' => true,
                    ],
                    'external' => [
                        'classname' => ExternalLink::class,
                        'enabled' => true,
                    ],
                    'file' => [
                        'classname' => FileLink::class,
                        'enabled' => true,
                    ],
                    'email' => [
                        'classname' => EmailLink::class,
                        'enabled' => true,
                    ],
                    'phone' => [
                        'classname' => PhoneLink::class,
                        'enabled' => true,
                    ],
                ],
                [
                    'Email Link',
                    'External Link',
                    'File Link',
                    'Phone Link',
                    'Site Tree Link',
                ],
            ],
            'file type disabled' => [
                [
                    'cms' => [
                        'classname' => SiteTreeLink::class,
                        'enabled' => true,
                    ],
                    'external' => [
                        'classname' => ExternalLink::class,
                        'enabled' => true,
                    ],
                    'file' => [
                        'classname' => FileLink::class,
                        'enabled' => false,
                    ],
                    'email' => [
                        'classname' => EmailLink::class,
                        'enabled' => true,
                    ],
                    'phone' => [
                        'classname' => PhoneLink::class,
                        'enabled' => true,
                    ],
                ],
                [
                    'Email Link',
                    'External Link',
                    'Phone Link',
                    'Site Tree Link',
                ],
            ],
            'phone and email types disabled' => [
                [
                    'cms' => [
                        'classname' => SiteTreeLink::class,
                        'enabled' => true,
                    ],
                    'external' => [
                        'classname' => ExternalLink::class,
                        'enabled' => true,
                    ],
                    'file' => [
                        'classname' => FileLink::class,
                        'enabled' => true,
                    ],
                    'email' => [
                        'classname' => EmailLink::class,
                        'enabled' => false,
                    ],
                    'phone' => [
                        'classname' => PhoneLink::class,
                        'enabled' => false,
                    ],
                ],
                [
                    'External Link',
                    'File Link',
                    'Site Tree Link',
                ],
            ],
        ];
    }

    public function testGetVersionedState(): void
    {
        // Versioned Link
        $link = Link::create(['Title' => 'abc']);
        $this->assertTrue(Link::has_extension(Versioned::class));
        $this->assertEquals('unsaved', $link->getVersionedState());
        $link->write();
        $this->assertEquals('draft', $link->getVersionedState());
        $link->publishSingle();
        $this->assertEquals('published', $link->getVersionedState());
        $link->Title = 'def';
        $link->write();
        $this->assertEquals('modified', $link->getVersionedState());
        // Unversioned Link
        Link::remove_extension(Versioned::class);
        $link = Link::create(['Title' => '123']);
        $this->assertEquals('unsaved', $link->getVersionedState());
        $link->write();
        $this->assertEquals('published', $link->getVersionedState());
    }

    /**
     * @param string $identifier
     * @param string $class
     * @param string $expected
     * @return void
     * @dataProvider linkUrlCasesDataProvider
     */
    public function testGetUrl(string $identifier, string $class, string $expected): void
    {
        /** @var Link $link */
        $link = $this->objFromFixture($class, $identifier);
        $this->assertSame($expected, $link->getURL(), 'We expect specific URL value');
    }

    public function linkUrlCasesDataProvider(): array
    {
        return [
            'internal link / page only' => [
                'page-link-page-only',
                SiteTreeLink::class,
                '/page-1',
            ],
            'internal link / anchor only' => [
                'page-link-anchor-only',
                SiteTreeLink::class,
                '/#my-anchor',
            ],
            'internal link / query string only' => [
                'page-link-query-string-only',
                SiteTreeLink::class,
                '/?param1=value1&param2=option2',
            ],
            'internal link / with anchor' => [
                'page-link-with-anchor',
                SiteTreeLink::class,
                '/page-1#my-anchor',
            ],
            'internal link / with query string' => [
                'page-link-with-query-string',
                SiteTreeLink::class,
                '/page-1?param1=value1&param2=option2',
            ],
            'internal link / with query string and anchor' => [
                'page-link-with-query-string-and-anchor',
                SiteTreeLink::class,
                '/page-1?param1=value1&param2=option2#my-anchor',
            ],
            'email link / with email' => [
                'email-link-with-email',
                EmailLink::class,
                'mailto:maxime@silverstripe.com',
            ],
            'email link / no email' => [
                'email-link-no-email',
                EmailLink::class,
                '',
            ],
            'external link / with URL' => [
                'external-link-with-url',
                ExternalLink::class,
                'https://google.com',
            ],
            'external link / no URL' => [
                'external-link-no-url',
                ExternalLink::class,
                '',
            ],
            'phone link / with phone' => [
                'phone-link-with-phone',
                PhoneLink::class,
                'tel:+64 4 978 7330',
            ],
            'phone link / no phone' => [
                'phone-link-no-phone',
                PhoneLink::class,
                '',
            ],
            'file link / with image' => [
                'file-link-with-image',
                FileLink::class,
                '/assets/ImageTest/600x400.png',
            ],
            'file link / no image' => [
                'file-link-no-image',
                FileLink::class,
                '',
            ],
        ];
    }

    function linkDefaultTitleDataProvider(): array
    {
        return [
            'page link' => [
                'identifier' =>  'page-link-1',
                'class' => SiteTreeLink::class,
                'expected' => 'PageLink1'
            ],
            'email link' => [
                'identifier' => 'email-link-with-email',
                'class' => EmailLink::class,
                'expected' => 'EmailLinkWithEmail'
            ],
            'external link' => [
                'identifier' => 'external-link-with-url',
                'class' => ExternalLink::class,
                'expected' => 'ExternalLinkWithUrl'
            ],
            'phone link' => [
                'identifier' => 'phone-link-with-phone',
                'class' => PhoneLink::class,
                'expected' => 'PhoneLinkWithPhone'
            ],
            'file link' => [
                'identifier' => 'file-link-no-image',
                'class' => FileLink::class,
                'expected' => 'File missing'
            ],
            'page link with default title' => [
                'identifier' => 'page-link-with-default-title',
                'class' => SiteTreeLink::class,
                'expected' => 'Page1'
            ],
            'page link no page default title' => [
                'identifier' => 'page-link-no-page-default-title',
                'class' => SiteTreeLink::class,
                'expected' => 'Page missing'
            ],
            'email link with default title' => [
                'identifier' => 'email-link-with-default-title',
                'class' => EmailLink::class,
                'expected' => 'maxime@silverstripe.com'
            ],
            'external link with default title' => [
                'identifier' => 'external-link-with-default-title',
                'class' => ExternalLink::class,
                'expected' => 'External Link: https://google.com'
            ],
            'phone link with default title' => [
                'identifier' => 'phone-link-with-default-title',
                'class' => PhoneLink::class,
                'expected' => '+64 4 978 7330'
            ],
            'file link with default title' => [
                'identifier' => 'file-link-with-default-title',
                'class' => FileLink::class,
                'expected' => '600x400.png'
            ],
        ];
    }

    /**
     * @dataProvider linkDefaultTitleDataProvider
     */
    public function testDefaultLinkTitle(string $identifier, string $class, string $expected): void
    {
        /** @var Link $link */
        $link = $this->objFromFixture($class, $identifier);

        $this->assertEquals($expected, $link->getDisplayTitle());
    }
}
