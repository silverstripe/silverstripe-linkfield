<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Forms\FieldList;

/**
 * A link to a phone number
 */
class PhoneLink extends Link
{
    private static string $table_name = 'LinkField_PhoneLink';

    private static array $db = [
        'Phone' => 'Varchar(255)',
    ];

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $linkField = $fields->dataFieldByName('Phone');
            $linkField->setTitle(_t('LinkField.PHONE_FIELD', 'Phone'));
        });
        return parent::getCMSFields();
    }

    public function getDescription(): string
    {
        return $this->Phone ?: '';
    }

    public function getURL(): string
    {
        return $this->Phone ? sprintf('tel:%s', $this->Phone) : '';
    }
}
