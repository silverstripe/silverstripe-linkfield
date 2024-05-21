<?php

namespace SilverStripe\LinkField\Tests\Extensions;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataExtension;

class ExternalLinkExtension extends DataExtension implements TestOnly
{
    protected function updateDefaultLinkTitle(&$defaultLinkTitle): void
    {
        $defaultLinkTitle = sprintf('External Link: %s', $this->owner->getURL());
    }
}
