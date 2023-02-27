<?php

namespace SilverStripe\LinkField\Form;

use LogicException;
use SilverStripe\Admin\Forms\LinkFormFactory;
use SilverStripe\Forms\HiddenField;
use SilverStripe\LinkField\Type\Type;

/**
 * Create Form schema for the LinkField based on a key provided by the request.
 */
class FormFactory extends LinkFormFactory
{
    protected function getFormFields($controller, $name, $context)
    {
        /** @var Type $type */
        $type = $context['LinkType'];

        if (empty($type) || !$type instanceof Type) {
            throw new LogicException(sprintf('%s: LinkType must be provided and must be an instance of Type', __CLASS__));
        }

        $fields = $type->scaffoldLinkFields([]);
        $fields->push(HiddenField::create('typeKey')->setValue($context['LinkTypeKey']));
        $this->extend('updateFormFields', $fields, $controller, $name, $context);

        return $fields;
    }

    protected function getValidator($controller, $name, $context)
    {
        return null;
    }
}
