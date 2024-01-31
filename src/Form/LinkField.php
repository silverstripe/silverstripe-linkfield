<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use SilverStripe\Forms\FormField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\Traits\AllowedLinkClassesTrait;
use SilverStripe\LinkField\Form\Traits\LinkFieldGetOwnerTrait;
use SilverStripe\Forms\HasOneRelationFieldInterface;

/**
 * Allows CMS users to edit a Link object.
 */
class LinkField extends FormField implements HasOneRelationFieldInterface
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

    public function getSchemaStateDefaults()
    {
        $data = parent::getSchemaStateDefaults();
        $data['canCreate'] = $this->getOwner()->canEdit();
        $data['readonly'] = $this->isReadonly();
        $data['disabled'] = $this->isDisabled();
        return $data;
    }

    protected function getDefaultAttributes(): array
    {
        $attributes = parent::getDefaultAttributes();
        $attributes['data-value'] = $this->Value();
        $attributes['data-can-create'] = $this->getOwner()->canEdit();
        $attributes['data-readonly'] = $this->isReadonly();
        $attributes['data-disabled'] = $this->isDisabled();
        $ownerFields = $this->getOwnerFields();
        $attributes['data-owner-id'] = $ownerFields['ID'];
        $attributes['data-owner-class'] = $ownerFields['Class'];
        $attributes['data-owner-relation'] = $ownerFields['Relation'];
        return $attributes;
    }

    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();
        $data['types'] = json_decode($this->getTypesProps());
        $ownerFields = $this->getOwnerFields();
        $data['ownerID'] = $ownerFields['ID'];
        $data['ownerClass'] = $ownerFields['Class'];
        $data['ownerRelation'] = $ownerFields['Relation'];
        return $data;
    }

    /**
     * Changes this field to the readonly field.
     */
    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        $clone->setReadonly(true);

        return $clone;
    }

    public function performDisabledTransformation()
    {
        $clone = clone $this;
        $clone->setDisabled(true);
        return $clone;
    }
}
