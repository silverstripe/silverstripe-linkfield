<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use LogicException;
use SilverStripe\Forms\FormField;
use SilverStripe\LinkField\Form\Traits\AllowedLinkClassesTrait;
use SilverStripe\LinkField\Form\Traits\LinkFieldGetOwnerTrait;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Relation;
use SilverStripe\ORM\SS_List;

/**
 * Allows CMS users to edit a Link object.
 */
class MultiLinkField extends FormField
{
    use AllowedLinkClassesTrait;
    use LinkFieldGetOwnerTrait;

    protected $schemaComponent = 'LinkField';

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_CUSTOM;

    protected $inputType = 'hidden';

    public function setValue($value, $data = null)
    {
        // If $data is a record, we can pull the value directly from it.
        // This mirrors MultiSelectField::setValue().
        if ($data instanceof DataObject) {
            $this->loadFrom($data);
            return $this;
        }

        $ids = $this->convertValueToArray($value);
        return parent::setValue($ids, $data);
    }

    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();
        $data['isMulti'] = true;
        $data['types'] = json_decode($this->getTypesProps());
        $ownerFields = $this->getOwnerFields();
        $data['ownerID'] = $ownerFields['ID'];
        $data['ownerClass'] = $ownerFields['Class'];
        $data['ownerRelation'] = $ownerFields['Relation'];
        return $data;
    }

    public function getSchemaStateDefaults()
    {
        $data = parent::getSchemaStateDefaults();
        $data['value'] = $this->getValueArray();
        $data['canCreate'] = $this->getOwner()->canEdit();
        $data['readonly'] = $this->isReadonly();
        return $data;
    }

    protected function getDefaultAttributes(): array
    {
        $attributes = parent::getDefaultAttributes();
        $attributes['data-value'] = $this->getValueArray();
        $attributes['data-can-create'] = $this->getOwner()->canEdit();
        $attributes['data-readonly'] = $this->isReadonly();
        $ownerFields = $this->getOwnerFields();
        $attributes['data-owner-id'] = $ownerFields['ID'];
        $attributes['data-owner-class'] = $ownerFields['Class'];
        $attributes['data-owner-relation'] = $ownerFields['Relation'];
        return $attributes;
    }

    /**
     * Extracts the value of this field, normalised as a non-associative array.
     */
    private function getValueArray(): array
    {
        return $this->convertValueToArray($this->Value());
    }

    /**
     * converts the value to an array if possible.
     * @throws LogicException if the type cannot be converted into an array.
     */
    private function convertValueToArray(mixed $value): array
    {
        // Prepare string by removing whitespace from the ends
        // A comma separated list of IDs will be turned into an array of IDs
        // Anything else will either get caught in the empty check or the !is_iterable check
        if (is_string($value)) {
            $value = $this->convertCommaSeparatedString(trim($value));
        }
        if (empty($value)) {
            return [];
        }
        if ($value instanceof SS_List) {
            return $value->column('ID');
        }
        if (!is_iterable($value)) {
            return [$value];
        }
        if (is_iterable($value) && !is_array($value)) {
            return [...$value];
        }
        if (is_array($value)) {
            return array_values($value);
        }
        // Theoretically this is unreachable - but let's have an exception just in case.
        throw new LogicException('Unexpected value type ' . gettype($value));
    }

    /**
     * converts a comma-separated string of integers into an array.
     * If any value is not an integer, it returns the original string.
     */
    private function convertCommaSeparatedString(string $string): string|array
    {
        // Split by comma and remove any whitespace between items
        $commaSeparated = array_map(fn ($string) => trim($string), explode(',', $string));

        // Stop cooercing if any value isn't an integer and just return the raw string instead.
        foreach ($commaSeparated as $index => $id) {
            if (!ctype_digit((string) $id) || $id != (int) $id) {
                return $string;
            }
            $commaSeparated[$index] = (int) $id;
        }

        return $commaSeparated;
    }

    /**
     * Load the value from the dataobject into this field
     */
    private function loadFrom(DataObject $record): void
    {
        $fieldName = $this->getName();
        if (empty($fieldName)) {
            return;
        }

        $relation = $record->hasMethod($fieldName)
            ? $record->$fieldName()
            : null;

        if (!$relation) {
            throw new LogicException("{$record->ClassName} is missing the relation '$fieldName'");
        }

        // Use Relation here rather than RelationList to allow for eagerloaded data or other shenanigans
        if (!$relation instanceof Relation) {
            throw new LogicException("'$fieldName()' method on {$record->ClassName} doesn't return a relation");
        }

        // Load ids from relation
        $value = array_values($relation->getIDList() ?? []);
        parent::setValue($value);
    }

    /**
     * Changes this field to the readonly field.
     */
    public function performReadonlyTransformation()
    {
        $clone = $this->castedCopy($this);
        $clone->setReadonly(true);

        return $clone;
    }
}
