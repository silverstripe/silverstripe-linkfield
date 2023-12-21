<?php

namespace SilverStripe\LinkField\Tests\Models\SiteTreeLinkTest;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

class TestSiteTreeCannotView extends SiteTree implements TestOnly
{
    public function canView($member = null)
    {
        return false;
    }
}
