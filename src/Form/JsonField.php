<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use InvalidArgumentException;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\LinkField\Models\Link;

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
        if (is_a($value, Link::class)) {
            $id = $value->ID;
        } else {
            $id = $value;
        }
        return parent::setValue($id, $data);
    }

    /**
     * @param DataObject|DataObjectInterface $record - A DataObject such as a Page
     * @return $this
     */
    public function saveInto(DataObjectInterface $record)
    {
        // Check required relation details are available
        $fieldname = $this->getName();
        if (!$fieldname) {
            return $this;
        }

        $linkID = $this->dataValue();
        $dbColumn = $fieldname . 'ID';
        $record->$dbColumn = $linkID;

        return $this;
    }
}
