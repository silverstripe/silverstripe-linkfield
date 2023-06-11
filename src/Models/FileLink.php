<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Assets\File;
use SilverStripe\i18n\i18n;

/**
 * A link to a File track in asset-admin
 *
 * @property int $FileID
 * @method File File()
 */
class FileLink extends Link
{
    private static $table_name = 'LinkField_FileLink';

    private static $has_one = [
        'File' => File::class
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
        $file = $this->File();

        return $file->exists() ? (string) $file->getURL() : '';
    }
}
