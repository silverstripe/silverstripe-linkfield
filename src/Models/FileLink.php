<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Assets\File;

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

    public function generateLinkDescription(array $data): string
    {
        $fileId = $data['FileID'] ?? null;

        if (!$fileId) {
            return '';
        }

        $file = File::get()->byID($fileId);

        return $file?->getFilename() ?? '';
    }

    public function LinkTypeHandlerName(): string
    {
        return 'InsertMediaModal';
    }

    public function getURL(): string
    {
        $file = $this->File();

        return $file->exists() ? (string) $file->getURL() : '';
    }
}
