<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

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

    private static string $icon = 'link';

    public function generateLinkDescription(array $data): string
    {
        return isset($data['Phone']) ? $data['Phone'] : '';
    }

    public function getURL(): string
    {
        return $this->Phone ? sprintf('tel:%s', $this->Phone) : '';
    }

    protected function FallbackTitle(): string
    {
        return $this->Phone ?: '';
    }
}
