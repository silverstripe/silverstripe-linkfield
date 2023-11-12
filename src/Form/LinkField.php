<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\LinkField\Models\Link;

/**
 * Allows CMS users to edit a Link object.
 */
class LinkField extends FormField
{
    protected $schemaComponent = 'LinkField';

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
