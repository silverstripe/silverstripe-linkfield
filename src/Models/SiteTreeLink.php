<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\CMS\Forms\AnchorSelectorField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TreeDropdownField;

/**
 * A link to a Page in the CMS
 *
 * @property int $PageID
 * @property string $Anchor
 * @method SiteTree Page()
 */
class SiteTreeLink extends Link
{
    private static string $table_name = 'LinkField_SiteTreeLink';

    private static array $db = [
        'Anchor' => 'Varchar',
    ];

    private static array $has_one = [
        'Page' => SiteTree::class,
    ];

    private static $icon = 'page';

    public function generateLinkDescription(array $data): string
    {
        $pageId = $data['PageID'] ?? null;

        if (!$pageId) {
            return '';
        }

        /** @var SiteTree $page */
        $page = SiteTree::get()->byID($pageId);

        if (!$page?->exists()) {
            return '';
        }

        return $page->URLSegment ?: '';
    }

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $titleField = $fields->dataFieldByName('Title');
        $titleField->setDescription('Auto generated from Page title if left blank');

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
                ->setDescription('Do not prepend "#". EG: "option1=value&option2=value2"')
        );

        return $fields;
    }

    public function getURL(): string
    {
        $url = $this->Page() ? $this->Page()->Link() : '';

        $this->extend('updateGetURLBeforeAnchor', $url);

        if ($this->Anchor) {
            $url .= '#' . $this->Anchor;
        }

        return $url;
    }

    public function getSummary(): string
    {
        $page = $this->Page;
        if ($page) {
            return $page->URLSegment;
        }

        return '';
    }

    protected function FallbackTitle(): string
    {
        return $this->Page ? ($this->Page->Title ?: '') : '';
    }
}
