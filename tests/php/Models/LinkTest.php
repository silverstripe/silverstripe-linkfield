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
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\ValidationException;
use SilverStripe\Versioned\Versioned;
use SilverStripe\LinkField\Tests\Extensions\ExternalLinkExtension;
use SilverStripe\LinkField\Tests\Models\LinkTest\LinkOwner;
use SilverStripe\LinkField\Services\LinkTypeService;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\LinkField\Tests\Controllers\LinkFieldControllerTest\TestPhoneLink;

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

    protected static $extra_dataobjects = [
        LinkOwner::class,
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

        $this->assertEquals('FormBuilderModal', $model->getLinkTypeHandlerName());
    }

    /**
     * @throws ValidationException
     */
    public function testSiteTreeLinkTitleFallback(): void
    {
        /** @var SiteTreeLink $model */
        $model = $this->objFromFixture(SiteTreeLink::class, 'page-link-1');

        $this->assertEquals('PageLink1', $model->LinkText, 'We expect to get a default Link title');

        /** @var SiteTree $page */
        $page = $this->objFromFixture(SiteTree::class, 'page-1');

        $model->PageID = $page->ID;
        $model->LinkText = null;
        $model->write();

        // The actual Database Title field should still be null
        $this->assertNull($model->getField('LinkText'));
        $this->assertEquals(null, $model->LinkText, 'We expect that link does not have a title');

        $customTitle = 'My custom title';
        $model->LinkText = $customTitle;
        $model->write();

        $this->assertEquals($customTitle, $model->LinkText, 'We expect to get the custom title not page title');
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
            [TestPhoneLink::class, false],
        ];
    }

    public function testGetVersionedState(): void
    {
        // Versioned Link
        $link = Link::create(['LinkText' => 'abc']);
        $this->assertTrue(Link::has_extension(Versioned::class));
        $this->assertEquals('unsaved', $link->getVersionedState());
        $link->write();
        $this->assertEquals('draft', $link->getVersionedState());
        $link->publishSingle();
        $this->assertEquals('published', $link->getVersionedState());
        $link->LinkText = 'def';
        $link->write();
        $this->assertEquals('modified', $link->getVersionedState());
        // Unversioned Link
        Link::remove_extension(Versioned::class);
        $link = Link::create(['LinkText' => '123']);
        $this->assertEquals('unsaved', $link->getVersionedState());
        $link->write();
        $this->assertEquals('unversioned', $link->getVersionedState());
    }

    public function provideGetUrl(): array
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

    /**
     * @param string $identifier
     * @param string $class
     * @param string $expected
     * @return void
     * @dataProvider provideGetUrl
     */
    public function testGetUrl(string $identifier, string $class, string $expected): void
    {
        /** @var Link $link */
        $link = $this->objFromFixture($class, $identifier);
        $this->assertSame($expected, $link->getURL(), 'We expect specific URL value');
    }

    function provideDefaultLinkTitle(): array
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
                'expected' => '(File missing)'
            ],
            'page link with default title' => [
                'identifier' => 'page-link-with-default-title',
                'class' => SiteTreeLink::class,
                'expected' => 'Page1'
            ],
            'page link no page default title' => [
                'identifier' => 'page-link-no-page-default-title',
                'class' => SiteTreeLink::class,
                'expected' => '(Page missing)'
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
                'expected' => 'Image1'
            ],
        ];
    }

    /**
     * @dataProvider provideDefaultLinkTitle
     */
    public function testDefaultLinkTitle(string $identifier, string $class, string $expected): void
    {
        /** @var Link $link */
        $link = $this->objFromFixture($class, $identifier);

        $this->assertEquals($expected, $link->getTitle());
    }

    public function provideOwner()
    {
        return [
            'null because there is no owner' => [
                'class' => EmailLink::class,
                'fixture' => 'email-link-with-email',
                'expected' => null,
            ],
            'null because the has_one is only stored on the owner' => [
                'class' => SiteTreeLink::class,
                'fixture' => 'page-link-1',
                // The owner has_one link, but the relationship wasn't saved in the link's Owner has_one.
                // See the LinkOwner.owns-has-one fixture.
                'expected' => null,
            ],
            'has_many owner always works' => [
                'class' => SiteTreeLink::class,
                'fixture' => 'page-link-page-only',
                'expected' => [
                    'class' => LinkOwner::class,
                    'fixture' => 'owns-has-many',
                ],
            ],
        ];
    }

    /**
     * Test the functionality of the overridden Owner method.
     * Note this is NOT explicitly testing multi-relational has_many relations pointing at the has_one since that's
     * a framework functionality, not a linkfield one.
     *
     * @dataProvider provideOwner
     */
    public function testOwner(string $class, string $fixture, ?array $expected)
    {
        $link = $this->objFromFixture($class, $fixture);
        if (is_array($expected)) {
            $expected = $this->idFromFixture($expected['class'], $expected['fixture']);
        }

        $this->assertSame($expected, $link->Owner()?->ID);
    }

    /**
     * Testing a scenario where a has_one to has_one is stored on the link.
     * Note we can't easily use providers here because of all the necessary logic to set this all up.
     */
    public function testOwnerHasOne()
    {
        $link = new Link();
        $link->write();
        $owner = new LinkOwner();
        $owner->write();

        // Add the owner relation on the link - without the relation
        $link->update([
            'OwnerID' => $owner->ID,
            'OwnerClass' => $owner->ClassName,
        ]);
        $link->write();

        // Clear out any previous-fetches of the owner component. We'll do this each time we check the owner.
        $link->flushCache(false);
        // The link tells us who the owner is - it doesn't have any way to tell that
        // the owner doesn't have a reciprocal relationship yet.
        $this->assertSame($owner->ID, $link->Owner()?->ID);

        // LinkField adds the relation name to the link, so this is what we'll normally see
        $link->OwnerRelation = 'Link';
        $link->write();

        // The actual has_one component is the LinkOwner record
        $link->flushCache(false);
        $this->assertSame($owner->ID, $link->getComponent('Owner')?->ID);
        // Owner returns null, because there is no reciprocal relationship from the LinkOwner record
        $link->flushCache(false);
        $this->assertSame(null, $link->Owner());

        // Add the link relation on the owner
        $owner->LinkID = $link->ID;
        $owner->write();

        // The link is now happy to declare its owner to us
        $link->flushCache(false);
        $this->assertSame($owner->ID, $link->Owner()?->ID);
    }

    /**
     * @dataProvider provideCanPermissions
     */
    public function testCanPermissions(string $linkPermission, string $ownerPermission)
    {
        $link = $this->objFromFixture(SiteTreeLink::class, 'page-link-page-only');
        $owner = $link->Owner();
        $permissionName = substr($linkPermission, 3);

        $this->assertTrue($owner?->exists());

        $owner->$ownerPermission = true;
        $this->assertTrue($link->$linkPermission());
        $this->assertTrue($link->can($permissionName));

        $owner->$ownerPermission = false;
        $this->assertFalse($link->$linkPermission());
        $this->assertFalse($link->can($permissionName));
    }

    public function provideCanPermissions()
    {
        return [
            'canView' => [
                'linkPermission' => 'canView',
                'ownerPermission' => 'canView',
            ],
            'canEdit' => [
                'linkPermission' => 'canEdit',
                'ownerPermission' => 'canEdit',
            ],
            'canDelete' => [
                'linkPermission' => 'canDelete',
                'ownerPermission' => 'canEdit',
            ],
        ];
    }

    public function testCanCreate()
    {
        $link = $this->objFromFixture(SiteTreeLink::class, 'page-link-page-only');
        $this->logOut();
        $this->assertTrue($link->canCreate());
        $this->assertTrue($link->can('Create'));
    }

    public function provideLinkType(): array
    {
        return [
            'email_link_type' => [
                'class' => EmailLink::class,
                'expected' => 'email',
            ],
            'external_link_type' => [
                'class' => ExternalLink::class,
                'expected' => 'external',
            ],
            'file_link_type' => [
                'class' => FileLink::class,
                'expected' => 'file',
            ],
            'phone_link_type' => [
                'class' => PhoneLink::class,
                'expected' => 'phone',
            ],
            'sitetree_link_type' => [
                'class' => SiteTreeLink::class,
                'expected' => 'sitetree',
            ],
            'testphone_link_type' => [
                'class' => TestPhoneLink::class,
                'expected' => 'testphone',
            ],
        ];
    }

    /**
     * @dataProvider provideLinkType
     */
    public function testGetShortCode($class, $expected): void
    {
        $linkClass = Injector::inst()->get($class);
        $this->assertSame($expected, $linkClass->getShortCode());
    }
}
