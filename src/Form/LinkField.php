<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use LogicException;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectInterface;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\Traits\AllowedLinkClassesTrait;
use SilverStripe\LinkField\Form\Traits\LinkFieldGetOwnerTrait;

/**
 * Allows CMS users to edit a Link object.
 */
class LinkField extends FormField
{
    use AllowedLinkClassesTrait;
    use LinkFieldGetOwnerTrait;

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

    public function getSchemaStateDefaults()
    {
        $data = parent::getSchemaStateDefaults();
        $data['canCreate'] = $this->getOwner()->canEdit();
        return $data;
    }

    protected function getDefaultAttributes(): array
    {
        $attributes = parent::getDefaultAttributes();
        $attributes['data-value'] = $this->Value();
        $attributes['data-can-create'] = $this->getOwner()->canEdit();
        return $attributes;
    }

    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();
        $data['types'] = json_decode($this->getTypesProps());
        return $data;
    }
}
