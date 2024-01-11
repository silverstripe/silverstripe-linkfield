<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;

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

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->replaceField('Email', EmailField::create(
                'Email',
                _t(__CLASS__ . '.EMAIL_FIELD', 'Email address'),
            ));
        });
        return parent::getCMSFields();
    }

    public function getDescription(): string
    {
        return $this->Email ?: '';
    }

    public function getURL(): string
    {
        return $this->Email ? sprintf('mailto:%s', $this->Email) : '';
    }
}
