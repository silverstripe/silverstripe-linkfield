<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Validation\CompositeValidator;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\Forms\UrlField;

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

    private static int $menu_priority = 20;

    private static $icon = 'font-icon-external-link';

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $field = UrlField::create('ExternalUrl', _t(__CLASS__ . '.EXTERNAL_URL_FIELD', 'External URL'));
            $field->setAllowedProtocols(['http', 'https']);
            $field->setDescription(_t(
                __CLASS__ . '.EXTERNAL_URL_FIELD_DESCRIPTION',
                'The URL must start with either http:// or https://'
            ));
            $fields->replaceField('ExternalUrl', $field);
        });
        return parent::getCMSFields();
    }

    public function getCMSCompositeValidator(): CompositeValidator
    {
        $validator = parent::getCMSCompositeValidator();
        $validator->addValidator(RequiredFieldsValidator::create(['ExternalUrl']));
        return $validator;
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
