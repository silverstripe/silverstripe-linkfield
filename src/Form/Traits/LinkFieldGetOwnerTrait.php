<?php

namespace SilverStripe\LinkField\Form\Traits;

use LogicException;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\Form;

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
}
