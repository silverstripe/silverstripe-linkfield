<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use InvalidArgumentException;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;

/**
 * Field designed to edit complex data passed as a JSON string. Other FormFields can be built on top of this one.
 *
 * It will output a hidden input with serialize JSON Data.
 */
abstract class JsonField extends FormField
{
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_CUSTOM;
    protected $inputType = 'hidden';

    public function setValue($value, $data = null)
    {
        if ($value && $value instanceof JsonData) {
            $value = json_encode($value);
        }

        return parent::setValue($value, $data);
    }

    /**
     * @param DataObject|DataObjectInterface $record
     * @return $this
     */
    public function saveInto(DataObjectInterface $record)
    {
        // Check required relation details are available
        $fieldname = $this->getName();
        if (!$fieldname) {
            return $this;
        }

        $dataValue = $this->dataValue();
        $value = is_string($dataValue) ? $this->parseString($this->dataValue()) : $dataValue;

        if ($class = DataObject::getSchema()->hasOneComponent(get_class($record), $fieldname)) {
            /** @var JsonData|DataObject $jsonDataObject */

            $jsonDataObjectID = $record->{"{$fieldname}ID"};
            if ($jsonDataObjectID && $jsonDataObject = $record->$fieldname) {
                if ($value) {
                    $jsonDataObject = $jsonDataObject->setData($value);
                    $this->extend('onBeforeLinkEdit', $jsonDataObject, $record);
                    $jsonDataObject->write();
                    $this->extend('onAfterLinkEdit', $jsonDataObject, $record);
                } else {
                    $this->extend('onBeforeLinkDelete', $jsonDataObject, $record);
                    $jsonDataObject->delete();
                    $record->{"{$fieldname}ID"} = 0;
                    $this->extend('onAfterLinkDelete', $jsonDataObject, $record);
                }
            } elseif ($value) {
                $jsonDataObject = new $class();
                $jsonDataObject = $jsonDataObject->setData($value);
                $this->extend('onBeforeLinkCreate', $jsonDataObject, $record);
                $jsonDataObject->write();
                $record->{"{$fieldname}ID"} = $jsonDataObject->ID;
                $this->extend('onAfterLinkCreate', $jsonDataObject, $record);
            }
        } elseif ((DataObject::getSchema()->databaseField(get_class($record), $fieldname))) {
            $record->{$fieldname} = $value;
        }

        return $this;
    }

    protected function parseString(string $value): ?array
    {
        if (empty($value)) {
            return null;
        }

        $data = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s: Could not parse provided JSON string. Failed with "%s"',
                    __CLASS__,
                    json_last_error_msg()
                )
            );
        }

        if (empty($data)) {
            return null;
        }

        return $data;
    }
}
