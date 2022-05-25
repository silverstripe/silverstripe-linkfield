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

    private static $icon = 'page';

    public function generateLinkDescription(array $data): array
    {
        $description = '';
        $title = empty($data['Title']) ? '' : $data['Title'];

        if (!empty($data['PageID'])) {
            $page = SiteTree::get()->byID($data['PageID']);
            if ($page) {
                $description = $page->URLSegment;
                if (empty($title)) {
                    $title = $page->Title;
                }
            }
        }

        return [
            'title' => $title,
            'description' => $description
        ];
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

            $titleField = $fields->dataFieldByName('Title');
            $titleField?->setDescription('Auto generated from Page title if left blank');

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

    public function getURL(): string
    {
        $page = $this->Page();
        $url = $page->exists() ? $page->Link() : '';
        $anchorSegment = $this->Anchor ? '#' . $this->Anchor : '';
        $queryStringSegment = $this->QueryString ? '?' . $this->QueryString : '';

        $this->extend('updateGetURLBeforeAnchor', $url);

        return Controller::join_links($url, $anchorSegment, $queryStringSegment);
    }

    protected function FallbackTitle(): string
    {
        return $this->Page ? $this->Page->Title : '';
    }
}
