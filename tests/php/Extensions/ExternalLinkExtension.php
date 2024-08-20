<?php

namespace SilverStripe\LinkField\Tests\Extensions;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Core\Extension;

class ExternalLinkExtension extends Extension implements TestOnly
{
    protected function updateDefaultLinkTitle(&$defaultLinkTitle): void
    {
        $defaultLinkTitle = sprintf('External Link: %s', $this->owner->getURL());
    }
}
