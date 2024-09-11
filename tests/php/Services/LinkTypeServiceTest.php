<?php

namespace SilverStripe\LinkField\Tests\Services;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Services\LinkTypeService;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\ExternalLink;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\PhoneLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\LinkField\Tests\Controllers\LinkFieldControllerTest\TestPhoneLink;
use PHPUnit\Framework\Attributes\DataProvider;

class LinkTypeServiceTest extends SapphireTest
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

    public function testGenerateAllLinkTypes()
    {
        $expected = [
          'email' => EmailLink::class,
          'external' => ExternalLink::class,
          'file' => FileLink::class,
          'phone' => PhoneLink::class,
          'sitetree' => SiteTreeLink::class,
          'testphone' => TestPhoneLink::class,
        ];

        $service = new LinkTypeService();

        // Get all unknown Link types
        $diff = array_diff($service->generateAllLinkTypes(), $this->link_types);
        // Leave only known Link subclasses
        $types = array_diff($service->generateAllLinkTypes(), $diff);

        $this->assertSame($expected, $types);
    }

    public static function keyClassDataProvider(): array
    {
        return [
            'sitetree_key' => [
              'sitetree',
              SiteTreeLink::class
            ],
            'email_key' => [
              'email',
              EmailLink::class,

            ],
            'external_key' => [
              'external',
              ExternalLink::class,
            ],
            'file_key' => [
              'file',
              FileLink::class,
            ],
            'phone_key' => [
              'phone',
              PhoneLink::class,
            ],
            'testphone_key' => [
              'testphone',
              TestPhoneLink::class,
            ],
        ];
    }

    #[DataProvider('keyClassDataProvider')]
    public function testByKey($key, $class)
    {
        $service = new LinkTypeService();
        $keyType = $service->byKey($key);
        $linkClass = Injector::inst()->get($class);

        $this->assertEquals($linkClass, $keyType);
    }

    #[DataProvider('keyClassDataProvider')]
    public function testKeyByClassName($key, $class)
    {
        $service = new LinkTypeService();
        $type = $service->keyByClassName($class);

        $this->assertEquals($key, $type);
    }
}
