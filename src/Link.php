<?php declare(strict_types=1);

namespace SilverStripe\Link;

use InvalidArgumentException;
use LogicException;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\FieldList;
use SilverStripe\Link\Type\Registry;
use SilverStripe\Link\Type\Type;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;

class Link extends DataObject implements JsonData, Type
{

    private static $db = [
        'Title' => 'Varchar',
        'OpenInNew' => 'Boolean'
    ];


    public function defineLinkTypeRequirements()
    {
        Requirements::add_i18n_javascript('silverstripe/link:client/lang', false, true);
        Requirements::javascript('silverstripe/link:client/dist/js/bundle.js');
        Requirements::css('silverstripe/link:client/dist/styles/bundle.css');
    }

    public function LinkTypeHandlerName(): string
    {
        return 'FormBuilderModal';
    }

    public function generateLinkDescription(array $data): string
    {
        return '';
    }

    public function LinkTypeTile(): string
    {
        return $this->i18n_singular_name();
    }

    public function scaffoldLinkFields(array $data): FieldList
    {
        return $this->getCMSFields();
    }


    function setData($data): JsonData
    {
        if (is_array($data)) {
            $this->update($data);
        } elseif ($data instanceof JsonData) {
            $array = $data->jsonSerialize();
            $this->update($array);
        } else {
            throw new InvalidArgumentException(sprintf('%s: $data must be an array or an instance of JsonSerialize.', __CLASS__));
        }

        return $this;
    }

    public function jsonSerialize()
    {
        $data = [];
        array_merge($data, $this->toMap());

        $typeKey = Registry::singleton()->keyByClassName(self::class);
        if ($typeKey) {
            $data['typeKey'] = $typeKey;
        }

        return $data;
    }

    public function loadLinkData(array $data): JsonData
    {
        $link = new self();
        $link->update($data);
        return $link;
    }
}
