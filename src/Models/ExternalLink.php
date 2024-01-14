<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Forms\FieldList;

/**
 * A link to an external URL.
 *
 * @property string $ExternalUrl
 */
class ExternalLink extends Link
{
    private static string $table_name = 'LinkField_ExternalLink';

    private static array $db = [
        'ExternalUrl' => 'Varchar',
    ];

    /**
     * Set the priority of this link type in the CMS menu
     */
    private static int $menu_priority = 20;

    private static $icon = 'font-icon-external-link';

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $linkField = $fields->dataFieldByName('ExternalUrl');
            $linkField->setTitle(_t(__CLASS__ . '.EXTERNAL_URL_FIELD', 'External url'));
        });
        return parent::getCMSFields();
    }

    public function getDescription(): string
    {
        return $this->ExternalUrl ?: '';
    }

    public function getURL(): string
    {
        return $this->ExternalUrl ?: '';
    }

    /**
     * The title that will be displayed in the dropdown
     * for selecting the link type to create.
     */
    public function getMenuTitle(): string
    {
        return _t(__CLASS__ . '.LINKLABEL', 'Link to external URL');
    }
}
