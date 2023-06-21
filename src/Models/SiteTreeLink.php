<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\CMS\Forms\AnchorSelectorField;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;

/**
 * A link to a Page in the CMS
 *
 * @property int $PageID
 * @property string $Anchor
 * @property string $QueryString
 * @method SiteTree Page()
 */
class SiteTreeLink extends Link
{
    private static $table_name = 'LinkField_SiteTreeLink';

    private static $db = [
        'Anchor' => 'Varchar',
        'QueryString' => 'Varchar',
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
        $this->beforeUpdateCMSFields(static function (FieldList $fields) {
            // Remove scaffolded fields to we don't have field name conflicts which would prevent field customisation
            $fields->removeByName([
                'PageID',
                'Anchor',
                'QueryString',
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
                $queryStringField = TextField::create('QueryString')
            );

            $queryStringField->setDescription('Do not prepend "?". EG: "option1=value&option2=value2"');

            $fields->insertAfter(
                'QueryString',
                $anchorField = AnchorSelectorField::create('Anchor')
            );

            $anchorField->setDescription(
                'Do not prepend "#". Anchor suggestions will be displayed once the linked page is attached.'
            );
        });

        return parent::getCMSFields();
    }

    public function getURL(): ?string
    {
        $page = $this->Page();
        $url = $page->exists() ? $page->Link() : '';
        $anchorSegment = $this->Anchor ? '#' . $this->Anchor : '';
        $queryStringSegment = $this->QueryString ? '?' . $this->QueryString : '';

        return Controller::join_links($url, $anchorSegment, $queryStringSegment);
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

        $page = $this->Page();

        if (!$page->exists()) {
            // We don't have a page to fall back to
            return null;
        }

        // Use page title as a default value in case CMS user didn't provide the title
        return $page->Title;
    }
}
