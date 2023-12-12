<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use LogicException;
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
            throw new LogicException('LinkField must have a name');
        }

        $linkID = $this->dataValue();
        $dbColumn = $fieldname . 'ID';
        $record->$dbColumn = $linkID;

        // Store the record as the owner of the link.
        // Required for permission checks, etc.
        $link = Link::get()->byID($linkID);
        if ($link) {
            $link->OwnerID = $record->ID;
            $link->OwnerClass = $record->ClassName;
            $link->OwnerRelation = $fieldname;
            $link->write();
        }

        return $this;
    }

    protected function getDefaultAttributes(): array
    {
        $attributes = parent::getDefaultAttributes();
        $attributes['data-value'] = $this->Value();
        return $attributes;
    }
}
