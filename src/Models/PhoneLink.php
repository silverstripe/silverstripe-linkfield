<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Dev\Deprecation;

/**
 * A link to a phone number
 *
 * @property string $Phone
 */
class PhoneLink extends Link
{
    private static string $table_name = 'LinkField_PhoneLink';

    private static array $db = [
        'Phone' => 'Varchar(255)',
    ];

    /**
     * @deprecated 3.0.0 Will be removed in linkfield v4 which will use getDescription() instead
     */
    public function generateLinkDescription(array $data): string
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed in linkfield v4 which will use getDescription() instead');
        });
        return isset($data['Phone']) ? $data['Phone'] : '';
    }

    public function getURL(): string
    {
        return $this->Phone ? sprintf('tel:%s', $this->Phone) : '';
    }
}
