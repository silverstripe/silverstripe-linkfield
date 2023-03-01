<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Assets\File;

/**
 * A link to a File track in asset-admin
 *
 * @property File $File
 * @property int $FileID
 */
class FileLink extends Link
{
    private static string $table_name = 'LinkField_FileLink';

    private static array $has_one = [
        'File' => File::class,
    ];

    public function generateLinkDescription(array $data): string
    {
        if (empty($data['FileID'])) {
            return '';
        }

        $file = File::get()->byID($data['FileID']);

        return $file ? $file->getFilename() : '';
    }

    public function LinkTypeHandlerName(): string
    {
        return 'InsertMediaModal';
    }

    public function getURL(): string
    {
        return $this->File ? $this->File->getURL() : '';
    }
}
