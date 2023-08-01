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

    private static $icon = 'menu-files';

    private static $modal_handler = 'InsertMediaModal';

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
        return $this->File?->getURL() ?? '';
    }

    public function getSummary(): string
    {
        $file = $this->File;
        if ($file) {
            return $file->getFilename();
        }

        return '';
    }

    protected function FallbackTitle(): string
    {
        return $this->File ? (string)$this->File->Title : '';
    }
}
