<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

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

    public function getDescription(): string
    {
        return $this->ExternalUrl ?: '';
    }

    public function getURL(): string
    {
        return $this->ExternalUrl ?: '';
    }
}
