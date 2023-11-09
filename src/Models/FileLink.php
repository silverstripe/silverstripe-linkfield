<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Assets\File;

class FileLink extends Link
{
    private static string $table_name = 'LinkField_FileLink';

    private static array $has_one = [
        'File' => File::class,
    ];

    public function getDescription(): string
    {
        return $this->File()?->getFilename() ?? '';
    }

    public function getURL(): string
    {
        $file = $this->File();
        return $file->exists() ? (string) $file->getURL() : '';
    }
}
