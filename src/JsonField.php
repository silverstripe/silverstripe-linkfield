<?php declare(strict_types=1);

namespace SilverStripe\Link;

use InvalidArgumentException;
use SilverStripe\Assets\File;
use SilverStripe\Forms\FileUploadReceiver;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\ORM\RelationList;
use SilverStripe\ORM\UnsavedRelationList;

/**
 * Field design to edit complex data passed as a JSON string. Other FormFields can be built on top of this one.
 *
 * It will output an hidden input with serialize JSON Data.
 */
abstract class JsonField extends FormField
{
    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_CUSTOM;
//    protected $inputType = 'hidden';


    public function setValue($value, $data = null)
    {
        if (is_string($value)) {
            $value = $this->parseString($value);
        }

        if ($value && !$value instanceof JsonData) {
            throw new InvalidArgumentException(sprintf(
                '%s can only accept %s as a value',
                __CLASS__,
                JsonData::class
            ));
        }

        return parent::setValue($value, $data);
    }

    /**
     * Returns the field value.
     *
     * @see FormField::setSubmittedValue()
     * @return mixed
     */
    public function Value()
    {
        return json_encode(parent::Value());
    }

    /**
     * Convert the provided string to a valid JsonData
     * @param string $value
     * @return JsonData
     * @throws InvalidArgumentException
     */
    abstract protected function parseString(string $value): ?JsonData;

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

        /** @var JsonData $value */
        $value = $this->dataValue();

        is_string($value) && var_dump($value) && die();

        if ($class = DataObject::getSchema()->hasOneComponent(get_class($record), $fieldname)) {
            /** @var JsonData|DataObject $jsonDataObject */

            $jsonDataObjectID = $record->{"{$fieldname}ID"};
            if ($jsonDataObjectID && $jsonDataObject = $record->$fieldname) {
                if ($value) {
                    $jsonDataObject->setData($value);
                    var_dump($jsonDataObject);
                    $jsonDataObject->write();
                } else {
                    $jsonDataObject->delete();
                    $record->{"{$fieldname}ID"} = 0;
                }
            } elseif ($value) {
                $jsonDataObject = new $class();
                $jsonDataObject->setData($value);
                $jsonDataObject->write();
                $record->{"{$fieldname}ID"} = $jsonDataObject->ID;
            }

        } elseif ((DataObject::getSchema()->databaseField(get_class($record), $fieldname))) {
            $jsonDataObject->setData($value);
        }
        return $this;
    }


}
