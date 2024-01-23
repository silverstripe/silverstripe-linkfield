<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ReadonlyField;

class LinkField_Readonly extends LinkField
{

    protected $readonly = true;

    public function Field($properties = [])
    {
        // Readonly field for display
        $field = new LinkField($this->name, $this->title);
        $field->setValue($this->Value());
        $field->setForm($this->form);

        // Store values to hidden field
        $valueField = new HiddenField($this->name);
        $valueField->setValue($this->Value());
        $valueField->setForm($this->form);

        return $field->Field() . $valueField->Field();
    }
}
