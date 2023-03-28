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

    /**
     * Try to populate link title from page title in case we don't have a title yet
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        $title = $this->getField('Title');

        if ($title) {
            // If we already have a title, we can just bail out without any changes
            return $title;
        }

        $page = $this->Page();

        if (!$page?->exists()) {
            // We don't have a page to fall back to
            return null;
        }

        // Use page title as a default value in case CMS user didn't provide the title
        return $page->Title;
    }
}
