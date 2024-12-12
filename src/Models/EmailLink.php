<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Validation\CompositeValidator;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;

/**
 * A link to an Email address.
 *
 * @property string $Email
 */
class EmailLink extends Link
{
    private static string $table_name = 'LinkField_EmailLink';

    private static array $db = [
        'Email' => 'Varchar(255)',
    ];

    private static int $menu_priority = 30;

    private static $icon = 'font-icon-p-mail';

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->replaceField('Email', EmailField::create(
                'Email',
                _t(__CLASS__ . '.EMAIL_FIELD', 'Email address'),
            ));
            $fields->removeByName('OpenInNew');
        });
        return parent::getCMSFields();
    }

    public function getCMSCompositeValidator(): CompositeValidator
    {
        $validator = parent::getCMSCompositeValidator();
        $validator->addValidator(RequiredFieldsValidator::create(['Email']));
        return $validator;
    }

    public function getDescription(): string
    {
        return $this->Email ?: '';
    }

    public function getURL(): string
    {
        return $this->Email ? sprintf('mailto:%s', $this->Email) : '';
    }

    /**
     * The title that will be displayed in the dropdown
     * for selecting the link type to create.
     */
    public function getMenuTitle(): string
    {
        return _t(__CLASS__ . '.LINKLABEL', 'Link to email address');
    }
}
