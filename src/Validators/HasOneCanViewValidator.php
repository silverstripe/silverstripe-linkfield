<?php

namespace SilverStripe\LinkField\Validators;

use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\Validator;
use SilverStripe\Forms\FormField;

/**
 * Call a canView() check to validate a has_one relation
 */
class HasOneCanViewValidator extends Validator
{
    /**
     * List of has_one relation fields (with 'ID' suffixed) that need to pass a canView() check
     */
    private array $relationFields;

    /**
     * Pass each field to be validated as a separate argument to the constructor
     * of this object. (an array of elements are ok).
     */
    public function __construct()
    {
        parent::__construct();
        $relationFields = func_get_args();
        if (isset($relationFields[0]) && is_array($relationFields[0])) {
            $relationFields = $relationFields[0];
        }
        foreach ($relationFields as $i => $relationField) {
            if (!preg_match('#ID$#', $relationField)) {
                $relationFields[$i] .= 'ID';
            }
        }
        $this->relationFields = $relationFields;
    }

    /**
     * Allows validation of fields via specification of a php function for
     * validation which is executed after the form is submitted.
     *
     * @param array $data
     * @return boolean
     */
    public function php($data)
    {
        $valid = true;
        $fields = $this->form->Fields();
        $dataObjectClassName = get_class($this->form->getRecord());
        $hasOnes = Config::inst()->get($dataObjectClassName, 'has_one');

        foreach ($this->relationFields as $fieldName) {
            if ($fieldName instanceof FormField) {
                $formField = $fieldName;
                $fieldName = $fieldName->getName();
            } else {
                $formField = $fields->dataFieldByName($fieldName);
            }

            if (!$formField) {
                continue;
            }

            $value ??= $data[$fieldName];
            if (!$value) {
                continue;
            }

            $relation = preg_replace('#ID$#', '', $fieldName);
            $relationClassName = $hasOnes[$relation];
            $relationObject = $relationClassName::get()->byID($value);

            if ($relationObject && $relationObject->canView()) {
                // It's valid
                // Note if $relationObject is null then still fail this CanView validator the same
                // way so that user cannot tell if the relation exists or not
                continue;
            }

            $errorMessage = _t(
                __CLASS__ . '.CANNOTBEVIEWED',
                '{name} cannot be viewed',
                [
                    'name' => strip_tags(
                        '"' . ($formField->Title() ? $formField->Title() : $fieldName) . '"'
                    )
                ]
            );
            if ($msg = $formField->getCustomValidationMessage()) {
                $errorMessage = $msg;
            }
            $this->validationError($fieldName, $errorMessage, 'required');
            $valid = false;
        }

        return $valid;
    }
}
