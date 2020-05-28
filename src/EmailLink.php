<?php declare(strict_types=1);

namespace SilverStripe\Link;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\i18n\i18n;
use SilverStripe\Link\Type\Type;
use SilverStripe\View\Requirements;

class EmailLink extends Link
{

    private static $db = [
        'Email' => 'Varchar(255)'
    ];


    public function generateLinkDescription(array $data): string
    {
        return isset($data['Email']) ? $data['Email'] : '';

    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->replaceField('Email', EmailField::create('Email'));

        return $fields;
    }

    public function getURL()
    {
        return $this->Email ? sprintf('mailto:%s', $this->Email) : '';
    }
}
