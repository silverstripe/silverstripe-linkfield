<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Dev\Deprecation;

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

    /**
     * @deprecated 3.0.0 Will be removed in linkfield v4 which will use getDescription() instead
     */
    public function generateLinkDescription(array $data): string
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed in linkfield v4 which will use getDescription() instead.');
        });
        return isset($data['ExternalUrl']) ? $data['ExternalUrl'] : '';
    }

    public function getURL(): string
    {
        return $this->ExternalUrl ?? '';
    }
}
