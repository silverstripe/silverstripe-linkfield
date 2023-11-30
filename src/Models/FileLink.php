<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Assets\File;
use SilverStripe\Forms\FieldList;

class FileLink extends Link
{
    private static string $table_name = 'LinkField_FileLink';

    private static array $has_one = [
        'File' => File::class,
    ];

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $linkField = $fields->dataFieldByName('File');
            $linkField->setTitle(_t('LinkField.FILE_FIELD', 'File'));
        });
        return parent::getCMSFields();
    }

    public function getDescription(): string
    {
        return $this->File()?->getFilename() ?? '';
    }

    public function getURL(): string
    {
        $file = $this->File();
        return $file->exists() ? (string) $file->getURL() : '';
    }

    public function getDefaultTitle(): string
    {
        $file = $this->File();
        if (!$file->exists()) {
            return _t(
                static::class . '.MISSING_DEFAULT_TITLE',
                'File missing',
            );
        }
        
        return (string) $this->getDescription();
    }
}
