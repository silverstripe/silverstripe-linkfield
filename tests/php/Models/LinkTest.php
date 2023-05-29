<?php

namespace SilverStripe\LinkField\Tests\Models;

use ReflectionException;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Config\Collections\MutableConfigCollectionInterface;
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

class LinkTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = 'LinkTest.yml';

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
}
