<?php

namespace SilverStripe\LinkField\Form\Traits;

use LogicException;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\Form;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Controllers\ElementalAreaController;

trait LinkFieldGetOwnerTrait
{
    private function getOwner(): DataObject
    {
        /** @var Form $form */
        $form = $this->getForm();
        $owner = $form->getRecord();
        if (!$owner) {
            throw new LogicException('Could not determine owner from form');
        }
        return $owner;
    }

    private function getOwnerFields(): array
    {
        $owner = $this->getOwner();
        $relation = $this->getName();
        // Elemental content block
        if (class_exists(BaseElement::class) && is_a($owner, BaseElement::class)) {
            // Remove namespaces from inline editable blocks
            // This will return an empty array for non-inline editable blocks (e.g. blocks in a gridfield)
            $arr = ElementalAreaController::removeNamespacesFromFields([$relation => ''], $owner->ID);
            if (array_key_exists(0, $arr)) {
                $relation = array_keys($arr)[0];
            }
        }
        return [
            'ID' => $owner->ID,
            'Class' => $owner::class,
            'Relation' => $relation,
        ];
    }
}
