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

    private static string $icon = 'p-mail';

    public function generateLinkDescription(array $data): string
    {
        return isset($data['Email']) ? $data['Email'] : '';
    }

    public function getCMSFields(): FieldList
    {
        $fields = parent::getCMSFields();

        $fields->replaceField('Email', EmailField::create('Email'));

        return $fields;
    }

    public function getURL(): string
    {
        return $this->Email ? sprintf('mailto:%s', $this->Email) : '';
    }


    protected function FallbackTitle(): string
    {
        return $this->Email ?: '';
    }
}
