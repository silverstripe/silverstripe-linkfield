<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Dev\Deprecation;

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

    /**
     * @deprecated 3.0.0 Will be removed in linkfield v4 which will use getDescription() instead
     */
    public function generateLinkDescription(array $data): string
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed in linkfield v4 which will use getDescription() instead.');
        });
        return isset($data['Email']) ? $data['Email'] : '';
    }

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(static function (FieldList $fields) {
            $fields->replaceField('Email', EmailField::create('Email'));
        });

        return parent::getCMSFields();
    }

    public function getURL(): string
    {
        return $this->Email ? sprintf('mailto:%s', $this->Email) : '';
    }
}
