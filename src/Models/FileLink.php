<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Assets\File;
use SilverStripe\Dev\Deprecation;

/**
 * A link to a File track in asset-admin
 *
 * @property int $FileID
 * @method File File()
 */
class FileLink extends Link
{
    private static string $table_name = 'LinkField_FileLink';

    private static array $has_one = [
        'File' => File::class,
    ];

    /**
     * @deprecated 3.0.0 Will be removed in linkfield v4 which will use getDescription() instead
     */
    public function generateLinkDescription(array $data): string
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed in linkfield v4 which will use getDescription() instead.');
        });
        $fileId = $data['FileID'] ?? null;

        if (!$fileId) {
            return '';
        }

        $file = File::get()->byID($fileId);

        return $file?->getFilename() ?? '';
    }

    /**
     * @deprecated 3.0.0 Will be removed in linkfield v4 which will use getLinkTypeHandlerName() instead
     */
    public function LinkTypeHandlerName(): string
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed in linkfield v4 which will use getLinkTypeHandlerName() instead.');
        });
        return 'InsertMediaModal';
    }

    public function getURL(): string
    {
        $file = $this->File();

        return $file->exists() ? (string) $file->getURL() : '';
    }
}
