<?php declare(strict_types=1);

namespace SilverStripe\Link;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\i18n\i18n;
use SilverStripe\Link\Type\Type;
use SilverStripe\View\Requirements;

class SiteTreeLink extends Link
{

    private static $has_one = [
        'SiteTree' => SiteTree::class
    ];


    public function generateLinkDescription(array $data): string
    {
        if (empty($data['SiteTreeID'])) {
            return '';
        }

        $page = SiteTree::get()->byID($data['SiteTreeID']);
        return $page ? $page->Title : '';

    }

    public function LinkTypeTile(): string
    {
        return _t(__CLASS__ . '.TITLE', 'Page link');
    }

    public function getCMSFields()
    {
        return parent::getCMSFields()
            ->addFieldToTab(
                'Root.Main',
                TreeDropdownField::create(
                    'SiteTreeID',
                    'Page',
                    SiteTree::class,
                    'ID',
                    'TreeTitle'
                )
            );
    }

    public function loadLinkData(array $data): JsonData
    {
        $link = new self();

        return $link;
    }


}
