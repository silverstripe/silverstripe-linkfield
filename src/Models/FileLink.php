<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Assets\File;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Validation\CompositeValidator;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;

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

    private static int $menu_priority = 10;

    private static $icon = 'font-icon-image';

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $linkField = $fields->dataFieldByName('File');
            $linkField->setTitle(_t(__CLASS__ . '.FILE_FIELD', 'File'));
        });
        return parent::getCMSFields();
    }

    public function getCMSCompositeValidator(): CompositeValidator
    {
        $validator = parent::getCMSCompositeValidator();
        $validator->addValidator(RequiredFieldsValidator::create(['File']));
        return $validator;
    }

    public function getDescription(): string
    {
        $file = $this->File();
        if (!$file?->exists()) {
            return _t(__CLASS__ . '.FILE_DOES_NOT_EXIST', 'File does not exist');
        }
        if (!$file->canView()) {
            return _t(__CLASS__ . '.CANNOT_VIEW_FILE', 'Cannot view file');
        }
        return $file->getFilename() ?? '';
    }

    public function getURL(): string
    {
        $file = $this->File();
        return $file->exists() ? (string) $file->getURL() : '';
    }

    /**
     * The title that will be displayed in the dropdown
     * for selecting the link type to create.
     */
    public function getMenuTitle(): string
    {
        return _t(__CLASS__ . '.LINKLABEL', 'Link to a file');
    }

    protected function getDefaultTitle(): string
    {
        $file = $this->File();
        if (!$file?->exists()) {
            return _t(__CLASS__ . '.MISSING_DEFAULT_TITLE', '(File missing)');
        }
        return $file->Title;
    }
}
