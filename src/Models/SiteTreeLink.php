<?php declare(strict_types=1);

namespace SilverStripe\Link\Models;

use SilverStripe\CMS\Forms\AnchorSelectorField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\TreeDropdownField;

/**
 * A link to a Page in the CMS.
 * @property SiteTree $Page
 * @property int $PageID
 * @property string $Anchor
 */
class SiteTreeLink extends Link
{

    private static $db = [
        'Anchor' => 'Varchar'
    ];

    private static $has_one = [
        'Page' => SiteTree::class
    ];


    public function generateLinkDescription(array $data): string
    {
        if (empty($data['PageID'])) {
            return '';
        }

        $page = SiteTree::get()->byID($data['PageID']);

        return $page ? $page->URLSegment : '';
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->insertAfter(
            'Title',
            TreeDropdownField::create(
                'PageID',
                'Page',
                SiteTree::class,
                'ID',
                'TreeTitle'
            )
        );

        $fields->insertAfter(
            'PageID',
            AnchorSelectorField::create('Anchor')
        );

        return $fields;
    }

    public function getURL()
    {
        $url = $this->Page ? $this->Page->Link() : '';
        if ($this->Anchor) {
            $url .= '#' . $this->Anchor;
        }
        return $url;
    }
}
