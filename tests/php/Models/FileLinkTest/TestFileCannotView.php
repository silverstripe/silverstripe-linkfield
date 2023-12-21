<?php

namespace SilverStripe\LinkField\Tests\Models\FileLinkTest;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Assets\File;

class TestFileCannotView extends File implements TestOnly
{
    public function canView($member = null)
    {
        return false;
    }
}
