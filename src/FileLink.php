<?php declare(strict_types=1);

namespace SilverStripe\Link;

use SilverStripe\Assets\File;
use SilverStripe\i18n\i18n;
use SilverStripe\Link\Type\Type;

/**
 * Class FileLink
 * @property File File
 */
class FileLink extends Link
{

    private static $has_one = [
        'File' => File::class
    ];


    public function generateLinkDescription(array $data): string
    {
        if (empty($data['FileID'])) {
            return '';
        }

        $file = File::get()->byID($data['FileID']);
        return $file ? $file->Title : '';
    }

    public function LinkTypeHandlerName(): string
    {
        return 'InsertMediaModal';
    }

    public function getURL()
    {
        return $this->File ? $this->File->getURL() : '';
    }

//    public function getCMSFields()
//    {
//        return parent::getCMSFields()
//            ->addFieldToTab(
//                'Root.Main',
//                TreeDropdownField::create(
//                    'SiteTreeID',
//                    'Page',
//                    SiteTree::class,
//                    'ID',
//                    'TreeTitle'
//                )
//            );
//    }
}
