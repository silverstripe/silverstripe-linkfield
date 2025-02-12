<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use DNADesign\Elemental\Controllers\ElementalAreaController;
use DNADesign\Elemental\Forms\EditFormFactory;
use DNADesign\Elemental\Models\BaseElement;
use InvalidArgumentException;
use LogicException;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\TextField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Services\LinkTypeService;
use SilverStripe\ORM\DataObject;
use SilverStripe\VersionedAdmin\Controllers\HistoryViewerController;

/**
 * Abstract form field for managing Link records
 */
abstract class AbstractLinkField extends FormField
{
    protected $schemaComponent = 'LinkField';

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_CUSTOM;

    protected $inputType = 'hidden';

    private array $allowedTypes = [];

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
     * Set which types of link are allowed for this field.
     * Types must be FQCN of Link subclasses.
     *
     * @param string[] $types
     * @throws InvalidArgumentException if $types is empty or any type in the array is invalid
     */
    public function setAllowedTypes(array $types): static
    {
        $this->validateTypes($types);
        $this->allowedTypes = $types;
        return $this;
    }

    /**
     * Get the types of link which are allowed for this field.
     */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    /**
     * The method returns a stringified JSON object
     * of available link types with additional parameters necessary
     * for work on the client side.
     * @throws InvalidArgumentException
     */
    public function getTypesProp(): string
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
                'handlerName' => $type->getLinkTypeHandlerName(),
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

    public function performReadonlyTransformation(): FormField
    {
        $clone = clone $this;
        $clone->setReadonly(true);

        return $clone;
    }

    public function performDisabledTransformation(): FormField
    {
        $clone = clone $this;
        $clone->setDisabled(true);
        return $clone;
    }

    public function getSchemaDataDefaults(): array
    {
        $data = parent::getSchemaDataDefaults();
        $data['types'] = json_decode($this->getTypesProp());
        $data['excludeLinkTextField'] = $this->getExcludeLinkTextField();
        $data['inHistoryViewer'] = $this->getInHistoryViewer();
        $ownerFields = $this->getOwnerFields();
        $data['ownerID'] = $ownerFields['ID'];
        $data['ownerClass'] = $ownerFields['Class'];
        $data['ownerRelation'] = $ownerFields['Relation'];
        return $data;
    }

    public function getSchemaStateDefaults(): array
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
        $attributes['data-value'] = $this->getFormattedValue();
        $attributes['data-can-create'] = $this->getOwner()->canEdit();
        $attributes['data-readonly'] = $this->isReadonly();
        $attributes['data-disabled'] = $this->isDisabled();
        $attributes['data-exclude-linktext-field'] = $this->getExcludeLinkTextField();
        $attributes['data-in-history-viewer'] = $this->getInHistoryViewer();
        $ownerFields = $this->getOwnerFields();
        $attributes['data-owner-id'] = $ownerFields['ID'];
        $attributes['data-owner-class'] = $ownerFields['Class'];
        $attributes['data-owner-relation'] = $ownerFields['Relation'];
        return $attributes;
    }

    private function getInHistoryViewer(): bool
    {
        return is_a($this->getForm()->getController(), HistoryViewerController::class);
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
            $factory = Injector::inst()->get(EditFormFactory::class);
            $field = TextField::create($relation);
            $fields = FieldList::create([$field]);
            $factory->removeNamespaceFromFields($fields, ['Record' => $owner]);
            $relation = $field->getName();
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
            $allLinkTypes = LinkTypeService::create()->generateAllLinkTypes();
            $fn = fn ($className) => Config::inst()->get($className, 'allowed_by_default');
            return array_filter($allLinkTypes, $fn);
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
    private function validateTypes(array $types): void
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

        foreach ($types as $type) {
            if (!is_subclass_of($type, Link::class)) {
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
    }
}
