<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use DNADesign\Elemental\Controllers\ElementalAreaController;
use DNADesign\Elemental\Models\BaseElement;
use InvalidArgumentException;
use LogicException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Services\LinkTypeService;
use SilverStripe\ORM\DataObject;

/**
 * Abstract form field for managing Link records
 */
abstract class AbstractLinkField extends FormField
{
    protected $schemaComponent = 'LinkField';

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_CUSTOM;

    protected $inputType = 'hidden';

    private array $allowed_types = [];

    private bool $excludeLinkTextField = false;

    public function setExcludeLinkTextField(bool $include): static
    {
        $this->excludeLinkTextField = $include;
        return $this;
    }

    public function getExcludeLinkTextField(): bool
    {
        return $this->excludeLinkTextField;
    }

    /**
     * Set allowed types for LinkField
     * @param string[] $types
     */
    public function setAllowedTypes(array $types): static
    {
        if ($this->validateTypes($types)) {
            $this->allowed_types = $types;
        }

        return $this;
    }

    /**
     * Get allowed types for LinkField
     */
    public function getAllowedTypes(): array
    {
        return $this->allowed_types;
    }

    /**
     * The method returns an associational array converted to a JSON string,
     * of available link types with additional parameters necessary
     * for full-fledged work on the client side.
     * @throws InvalidArgumentException
     */
    public function getTypesProps(): string
    {
        $typesList = [];
        $typeDefinitions = $this->generateAllowedTypes();
        $allTypes = LinkTypeService::create()->generateAllLinkTypes();
        foreach ($allTypes as $key => $class) {
            $type = Injector::inst()->get($class);
            $allowed = array_key_exists($key, $typeDefinitions) && $type->canCreate();
            $typesList[$key] = [
                'key' => $key,
                'title' => $type->getMenuTitle(),
                'handlerName' => $type->LinkTypeHandlerName(),
                'priority' => $class::config()->get('menu_priority'),
                'icon' => $class::config()->get('icon'),
                'allowed' => $allowed,
            ];
        }
        uasort($typesList, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return json_encode($typesList);
    }

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

    public function getSchemaDataDefaults()
    {
        $data = parent::getSchemaDataDefaults();
        $data['types'] = json_decode($this->getTypesProps());
        $data['excludeLinkTextField'] = $this->getExcludeLinkTextField();
        $ownerFields = $this->getOwnerFields();
        $data['ownerID'] = $ownerFields['ID'];
        $data['ownerClass'] = $ownerFields['Class'];
        $data['ownerRelation'] = $ownerFields['Relation'];
        return $data;
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
        $attributes['data-exclude-linktext-field'] = $this->getExcludeLinkTextField();
        $ownerFields = $this->getOwnerFields();
        $attributes['data-owner-id'] = $ownerFields['ID'];
        $attributes['data-owner-class'] = $ownerFields['Class'];
        $attributes['data-owner-relation'] = $ownerFields['Relation'];
        return $attributes;
    }

    protected function getOwner(): DataObject
    {
        /** @var Form $form */
        $form = $this->getForm();
        $owner = $form->getRecord();
        if (!$owner) {
            throw new LogicException('Could not determine owner from form');
        }
        return $owner;
    }

    protected function getOwnerFields(): array
    {
        $owner = $this->getOwner();
        $relation = $this->getName();
        // Elemental content block
        if (class_exists(BaseElement::class) && is_a($owner, BaseElement::class)) {
            // Remove namespaces from inline editable blocks
            // This will return an empty array for non-inline editable blocks (e.g. blocks in a gridfield)
            $arr = ElementalAreaController::removeNamespacesFromFields([$relation => ''], $owner->ID);
            if (!empty($arr)) {
                $relation = array_key_first($arr);
            }
        }
        return [
            'ID' => $owner->ID,
            'Class' => $owner::class,
            'Relation' => $relation,
        ];
    }

    /**
     * Generate allowed types with key => value pair
     * Example: ['cms' => SiteTreeLink::class]
     * @param string[] $types
     */
    private function generateAllowedTypes(): array
    {
        $typeDefinitions = $this->getAllowedTypes() ?? [];

        if (empty($typeDefinitions)) {
            return LinkTypeService::create()->generateAllLinkTypes();
        }

        $result = array();
        foreach ($typeDefinitions as $class) {
            if (is_subclass_of($class, Link::class)) {
                $type = Injector::inst()->get($class)->getShortCode();
                $result[$type] = $class;
            }
        }
        return $result;
    }

    /**
     * Validate types that they are subclasses of Link
     * @param string[] $types
     * @throws InvalidArgumentException
     */
    private function validateTypes(array $types): bool
    {
        if (empty($types)) {
            throw new InvalidArgumentException(
                _t(
                    __TRAIT__ . '.INVALID_TYPECLASS_EMPTY',
                    '"{class}": Allowed types cannot be empty',
                    ['class' => static::class],
                ),
            );
        }

        $validClasses = [];
        foreach ($types as $type) {
            if (is_subclass_of($type, Link::class)) {
                $validClasses[] = $type;
            } else {
                throw new InvalidArgumentException(
                    _t(
                        __TRAIT__ . '.INVALID_TYPECLASS',
                        '"{class}": {typeclass} is not a valid Link Type',
                        ['class' => static::class, 'typeclass' => $type],
                        sprintf(
                            '"%s": %s is not a valid Link Type',
                            static::class,
                            $type,
                        ),
                    ),
                );
            }
        }

        return count($validClasses) > 0;
    }
}
