<?php

namespace SilverStripe\LinkField\Tests\Models;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\SiteTreeLink;
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
}
