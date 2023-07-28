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
use SilverStripe\LinkField\Type\Type;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Versioned\Versioned;

class LinkTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = 'LinkTest.yml';

    protected function setUp(): void
    {
        parent::setUp();

        TestAssetStore::activate('ImageTest');

        /** @var Image $image */
        $image = $this->objFromFixture(Image::class, 'image-1');
        $image->setFromLocalFile(Director::baseFolder() . '/tests/resources/600x400.png');
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
        $model->Title = '';
        $model->write();

        $this->assertEquals($page->Title, $model->Title, 'We expect to get the linked Page title');

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
            $enabledTypes = array_map(static function (Type $type): string {
                return $type->LinkTypeTile();
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

    /**
     * @param string $identifier
     * @param string $class
     * @param string $expected
     * @return void
     * @dataProvider linkUrlCasesDataProvider
     */
    public function testGetUrl(string $identifier, string $class, string $expected): void
    {
        Versioned::withVersionedMode(function () use ($identifier, $class, $expected): void {
            Versioned::set_stage(Versioned::LIVE);

            /** @var Link $link */
            $link = $this->objFromFixture($class, $identifier);

            $this->assertSame($expected, $link->getURL(), 'We expect specific URL value');
        });
    }

    public function linkUrlCasesDataProvider(): array
    {
        return [
            'internal link / page only' => [
                'page-link-page-only',
                SiteTreeLink::class,
                '/page-1/',
            ],
            'internal link / anchor only' => [
                'page-link-anchor-only',
                SiteTreeLink::class,
                '#my-anchor',
            ],
            'internal link / query string only' => [
                'page-link-query-string-only',
                SiteTreeLink::class,
                '?param1=value1&param2=option2',
            ],
            'internal link / with anchor' => [
                'page-link-with-anchor',
                SiteTreeLink::class,
                '/page-1/#my-anchor',
            ],
            'internal link / with query string' => [
                'page-link-with-query-string',
                SiteTreeLink::class,
                '/page-1/?param1=value1&param2=option2',
            ],
            'internal link / with query string and anchor' => [
                'page-link-with-query-string-and-anchor',
                SiteTreeLink::class,
                '/page-1/?param1=value1&param2=option2#my-anchor',
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
}
