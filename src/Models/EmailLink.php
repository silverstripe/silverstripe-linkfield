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

    public function generateLinkDescription(array $data): string
    {
        return isset($data['Email']) ? $data['Email'] : '';
    }

    public function getCMSFields(): FieldList
    {
        $self = $this;
        $this->beforeUpdateCMSFields(static function (FieldList $fields) use (
            $self
        ) {
            $fields->replaceField(
                'Email',
                EmailField::create('Email', $self->fieldLabel('Email'))
            );
        });

        return parent::getCMSFields();
    }

    public function getURL(): string
    {
        return $this->Email ? sprintf('mailto:%s', $this->Email) : '';
    }

    public function fieldLabels($includerelations = true)
    {
        return array_merge(parent::fieldLabels($includerelations), [
            'Email' => _t(__CLASS__ . '.Email', 'Email'),
        ]);
    }
}
