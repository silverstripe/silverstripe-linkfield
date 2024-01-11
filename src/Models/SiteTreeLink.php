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
    private static string $table_name = 'LinkField_SiteTreeLink';

    private static array $db = [
        'Anchor' => 'Varchar',
        'QueryString' => 'Varchar',
    ];

    private static array $has_one = [
        'Page' => SiteTree::class,
    ];

    public function getDescription(): string
    {
        $page = $this->Page();
        if (!$page?->exists()) {
            return _t(__CLASS__ . '.PAGE_DOES_NOT_EXIST', 'Page does not exist');
        }
        if (!$page->canView()) {
            return _t(__CLASS__ . '.CANNOT_VIEW_PAGE', 'Cannot view page');
        }
        return $page->URLSegment ?? '';
    }

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            // Remove scaffolded fields to we don't have field name conflicts which would prevent field customisation
            $fields->removeByName([
                'PageID',
                'Anchor',
                'QueryString',
            ]);

            $titleField = $fields->dataFieldByName('Title');
            $titleField?->setDescription(
                _t(
                    __CLASS__ . '.TITLE_DESCRIPTION',
                    'Auto generated from Page title if left blank',
                ),
            );

            $fields->insertAfter(
                'Title',
                TreeDropdownField::create(
                    'PageID',
                    _t(__CLASS__ . '.PAGE_FIELD_TITLE', 'Page'),
                    SiteTree::class,
                    'ID',
                    'TreeTitle'
                )
            );

            $fields->insertAfter(
                'PageID',
                $queryStringField = TextField::create(
                    'QueryString',
                    _t(__CLASS__ . '.QUERY_FIELD_TITLE', 'Query string'),
                )
            );

            $queryStringField->setDescription(
                _t(
                    __CLASS__ . '.QUERY_STRING_DESCRIPTION',
                    'Do not prepend "?". EG: "option1=value&option2=value2"',
                ),
            );

            $fields->insertAfter(
                'QueryString',
                $anchorField = AnchorSelectorField::create(
                    'Anchor',
                    _t(__CLASS__ . '.ANCHOR_FIELD_TITLE', 'Anchor')
                )
            );

            $anchorField->setDescription(
                _t(
                    __CLASS__ . '.ANCHOR_DESCRIPTION',
                    'Do not prepend "#". Anchor suggestions will be displayed once the linked page is attached.',
                ),
            );
        });

        return parent::getCMSFields();
    }

    public function getURL(): string
    {
        $page = $this->Page();
        $url = $page->exists() ? $page->Link() : '';
        $anchorSegment = $this->Anchor ? '#' . $this->Anchor : '';
        $queryStringSegment = $this->QueryString ? '?' . $this->QueryString : '';

        $this->extend('updateGetURLBeforeAnchor', $url);

        return Controller::join_links($url, $anchorSegment, $queryStringSegment);
    }

    public function getDefaultTitle(): string
    {
        $page = $this->Page();
        if (!$page->exists()) {
            return _t(
                static::class . '.MISSING_DEFAULT_TITLE',
                'Page missing',
            );
        }
        if (!$page->canView()) {
            return '';
        }
        return $page->Title;
    }
}
