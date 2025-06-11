<?php

namespace SilverStripe\LinkField\Tests\Behat\Context\Extension;

use SilverStripe\Core\Extension;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\LinkField\Models\ExternalLink;

/**
 * Used in conjunction with SilverStripe\FrameworkTest\LinkField\Extensions\CompanyExtension
 * to test adding links to unsaved parent records
 */
class LinkCompanyExtension extends Extension implements TestOnly
{
    protected function updateCMSFields(FieldList $fields): void
    {
        // Re-create these fields so that has_many field is displayed on unsaved records (it would be skipped during
        // automatic scaffolding) and to ensure they're at the top of the form so are visible in any screenshots
        $fields->removeByName(['CompanyWebSiteLink', 'ManyCompanyWebSiteLink']);
        $fields->addFieldsToTab(
            'Root.Main',
            [
                LinkField::create('CompanyWebSiteLink', 'Company web site link')
                    ->setAllowedTypes([ExternalLink::class]), // Matches CompanyExtension
                MultiLinkField::create('ManyCompanyWebSiteLink', 'Many company web site links'),
            ],
            'Category',
        );
    }
}
