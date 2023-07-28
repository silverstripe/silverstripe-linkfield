<?php

namespace SilverStripe\LinkField\Form;

use LogicException;
use SilverStripe\Admin\Forms\LinkFormFactory;
use SilverStripe\Forms\HiddenField;
use SilverStripe\LinkField\Type\Type;
use SilverStripe\ORM\DataObject;

/**
 * Create Form schema for the LinkField based on a key provided by the request.
 */
class FormFactory extends LinkFormFactory
{
    protected function getFormFields($controller, $name, $context)
    {
        /** @var Type $type */
        $type = $context['LinkType'];

        if (!$type instanceof Type) {
            throw new LogicException(sprintf('%s: LinkType must be provided and must be an instance of Type', static::class));
        }

        // Pass on any available link data
        $linkData = array_key_exists('LinkData', $context)
            ? $context['LinkData']
            : [];
        $fields = $type->scaffoldLinkFields($linkData);
        $fields->push(HiddenField::create('typeKey')->setValue($context['LinkTypeKey']));
        $this->extend('updateFormFields', $fields, $controller, $name, $context);

        return $fields;
    }

    protected function getValidator($controller, $name, $context)
    {
        if (!array_key_exists('LinkType', $context)) {
            return null;
        }

        /** @var DataObject|Type $type */
        $type = $context['LinkType'];

        return $type->getCMSCompositeValidator();
    }
}
