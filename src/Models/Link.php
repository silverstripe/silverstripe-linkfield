<?php declare(strict_types=1);

namespace SilverStripe\Link\Models;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Link\JsonData;
use SilverStripe\Link\Type\Registry;
use SilverStripe\Link\Type\Type;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;

/**
 * A Link Data Object. This class should be subclass and you should never directly interact with a plain Link instance.
 *
 * @property string $Title
 * @property bool $OpenInNew
 */
class Link extends DataObject implements JsonData, Type
{

    private static $db = [
        'Title' => 'Varchar',
        'OpenInNew' => 'Boolean'
    ];


    public function defineLinkTypeRequirements()
    {
        Requirements::add_i18n_javascript('silverstripe/linkfield:client/lang', false, true);
        Requirements::javascript('silverstripe/linkfield:client/dist/js/bundle.js');
        Requirements::css('silverstripe/linkfield:client/dist/styles/bundle.css');
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
        if (is_string($data)) {
            $data = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException(sprintf(
                    '%s: Decoding json string failred with "%s"',
                    __CLASS__,
                    json_last_error_msg()
                ));
            }
        } elseif ($data instanceof JsonData) {
            $data = $data->jsonSerialize();
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf('%s: Could not convert $data to an array.', __CLASS__));
        }

        if (empty($data['typeKey'])) {
            throw new InvalidArgumentException(sprintf('%s: $data does not have a typeKey.', __CLASS__));
        }

        $type = Registry::singleton()->byKey($data['typeKey']);
        if (empty($type)) {
            throw new InvalidArgumentException(sprintf('%s: %s is not a registered Link Type.', __CLASS__, $data['typeKey']));
        }

        $jsonData = $this;
        if ($this->ClassName !== get_class($type)) {
            if ($this->isInDB()) {
                $jsonData = $this->newClassInstance(get_class($type));
            } else {
                $jsonData = Injector::inst()->create(get_class($type));
            }

        }

        foreach ($data as $key => $value) {
            if ($jsonData->hasField($key)) {
                $jsonData->setField($key, $value);
            }
        }

        return $jsonData;
    }

    public function jsonSerialize()
    {
        $typeKey = Registry::singleton()->keyByClassName(static::class);
        if (empty($typeKey)) {
            return [];
        }

        $data = $this->toMap();
        $data['typeKey'] = $typeKey;

        unset($data['ClassName']);
        unset($data['RecordClassName']);

        return $data;
    }

    public function loadLinkData(array $data): JsonData
    {
        $link = new static();
        foreach ($data as $key => $value) {
            if ($link->hasField($key)) {
                $link->setField($key, $value);
            }
        }
        return $link;
    }

    /**
     * Return a rendered version of this form.
     *
     * This is returned when you access a form as $FormObject rather
     * than <% with FormObject %>
     *
     * @return DBHTMLText
     */
    public function forTemplate()
    {
        return $this->renderWith([self::class]);
    }

}
