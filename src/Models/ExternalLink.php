<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

/**
 * An link to an external URL.
 *
 * @property string $ExternalUrl
 */
class ExternalLink extends Link
{
    private static $table_name = 'LinkField_ExternalLink';

    private static $db = [
        'ExternalUrl' => 'Varchar'
    ];

    public function generateLinkDescription(array $data): string
    {
        return isset($data['ExternalUrl']) ? $data['ExternalUrl'] : '';
    }

    public function getURL()
    {
        return (string) $this->ExternalUrl;
    }
}
