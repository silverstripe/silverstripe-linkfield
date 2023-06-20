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
        if (empty($data['PageID'])) {
            return '';
        }

        /** @var SiteTree $page */
        $page = SiteTree::get()->byID($data['PageID']);

        if (!$page || !$page->exists()) {
            return '';
        }

        return $page->URLSegment ?: '';
    }

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(static function (FieldList $fields) {
            // Remove scaffolded fields to we don't have field name conflicts which would prevent field customisation
            $fields->removeByName([
                'PageID',
                'Anchor',
            ]);

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
        });

        return parent::getCMSFields();
    }

    public function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        $this->populateTitle();
    }

    public function getURL(): string
    {
        $page = $this->Page();
        $url = $page->exists() ? $page->Link() : '';

        $this->extend('updateGetURLBeforeAnchor', $url);

        if ($this->Anchor) {
            $url .= '#' . $this->Anchor;
        }

        return $url;
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

        $page = $this->Page();

        if (!$page->exists()) {
            // We don't have a page to fall back to
            return null;
        }

        // Use page title as a default value in case CMS user didn't provide the title
        return $page->Title;
    }
}
