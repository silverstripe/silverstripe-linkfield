<?php

namespace SilverStripe\LinkField\Tests\Behat\Context\Extension;

use RuntimeException;
use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;

/**
 * Used in conjunction with SilverStripe\FrameworkTest\LinkField\Extensions\ElementContentExtension
 * and SilverStripe\FrameworkTest\LinkField\Extensions\LinkPageExtension
 * to test limiting the number of links in a MultiLinkField.
 */
class LinkLimitExtension extends Extension implements TestOnly
{
    protected function updateCMSFields(FieldList $fields): void
    {
        // LinkPageExtension calls the field "HasManyLinks"
        $linkField = $fields->dataFieldByName('HasManyLinks');
        if (!$linkField) {
            // ElementContentExtension calls the field "ManyLinks"
            $linkField = $fields->dataFieldByName('ManyLinks');
        }
        $linkField?->setMaximumLinks(2);
    }
}
