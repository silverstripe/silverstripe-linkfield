<?php declare(strict_types=1);

namespace SilverStripe\Link;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\i18n\i18n;
use SilverStripe\Link\Type\Type;
use SilverStripe\View\Requirements;

class ExternalLink extends Link
{

    private static $db = [
        'ExternalUrl' => 'Varchar'
    ];


    public function generateLinkDescription(array $data): string
    {
        return isset($data['ExternalUrl']) ? $data['ExternalUrl'] : '';

    }

    public function getURL()
    {
        return $this->ExternalUrl;
    }
}
