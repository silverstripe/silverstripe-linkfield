<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Assets\File;
use SilverStripe\Forms\FieldList;

/**
 * A link to a File in the CMS
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
}
