<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\CMS\Forms\AnchorSelectorField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TreeDropdownField;

/**
 * A link to a Page in the CMS
 *
 * @property SiteTree $Page
 * @property int $PageID
 * @property string $Anchor
 */
class SiteTreeLink extends Link
{

    private static $table_name = 'LinkField_SiteTreeLink';

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

        /** @var SiteTree $page */
        $page = SiteTree::get()->byID($data['PageID']);

        return $page ? $page->URLSegment : '';
    }

    public function getCMSFields(): FieldList
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

    public function getURL(): ?string
    {
        $url = $this->Page ? $this->Page->Link() : '';

        if ($this->Anchor) {
            $url .= '#' . $this->Anchor;
        }

        return $url;
    }

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        $this->populateTitle();
    }

    protected function populateTitle(): void
    {
        $title = $this->getTitleFromPage();
        $this->extend('updateGetTitleFromPage', $title);
        $this->Title = $title;
    }

    /**
     * Try to populate link title from page title in case we don't have a title yet
     *
     * @return string|null
     */
    protected function getTitleFromPage(): ?string
    {
        if ($this->Title) {
            // If we already have a title, we can just bail out without any changes
            return $this->Title;
        }

        $page = $this->Page;

        if (!$page || !$page->exists()) {
            // We don't have a page to fall back to
            return null;
        }

        // Use page title as a default value in case CMS user didn't provide the title
        return $this->Page->Title;
    }
}
