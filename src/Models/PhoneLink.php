<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;

/**
 * A link to a phone number
 *
 * @property string $Phone
 */
class PhoneLink extends Link
{

    private static $table_name = 'LinkField_PhoneLink';

    private static $db = [
        'Phone' => 'Varchar(255)'
    ];

    public function generateLinkDescription(array $data): string
    {
        return isset($data['Phone']) ? $data['Phone'] : '';
    }

    public function getURL()
    {
        return $this->Phone ? sprintf('tel:%s', $this->Phone) : '';
    }
}
