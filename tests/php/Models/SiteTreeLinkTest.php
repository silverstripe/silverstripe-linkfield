<?php

namespace SilverStripe\LinkField\Tests\Models;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\LinkField\Tests\Models\SiteTreeLinkTest\TestSiteTreeCanView;
use SilverStripe\LinkField\Tests\Models\SiteTreeLinkTest\TestSiteTreeCannotView;

class SiteTreeLinkTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        TestSiteTreeCanView::class,
        TestSiteTreeCannotView::class,
    ];

    public function testGetDescription(): void
    {
        // SiteTreeLink without a page
        $link = SiteTreeLink::create();
        $this->assertSame('Page does not exist', $link->getDescription());
        // SiteTreeLink with a page though cannot view the page
        $page = new TestSiteTreeCannotView(['URLSegment' => 'test-a']);
        $page->write();
        $link->Page = $page->ID;
        $link->write();
        $this->assertSame('Cannot view page', $link->getDescription());
        // SiteTreeLink with a page that and can view the page
        $page = new TestSiteTreeCanView(['URLSegment' => 'test-b']);
        $page->write();
        $link->Page = $page->ID;
        $link->write();
        $this->assertSame('test-b', $link->getDescription());
    }

    public function testGetDefaultTitle(): void
    {
        // Page does not exist
        $link = SiteTreeLink::create();
        $this->assertSame('(Page missing)', $link->getDefaultTitle());
        // Page exists
        $page = new TestSiteTreeCanView(['Title' => 'My test page']);
        $page->write();
        $link->Page = $page->ID;
        $link->write();
        $this->assertSame('My test page', $link->getDefaultTitle());
    }
}
