<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\ReadonlyField;

class MultiLinkField_Readonly extends MultiLinkField
{

    protected $readonly = true;

    public function Field($properties = [])
    {
        // Readonly field for display
        $field = new MultiLinkField($this->name, $this->title);
        $field->setValue($this->getValueArray());
        $field->setForm($this->form);

        // Store values to hidden field
        $valueField = new HiddenField($this->name);
        $valueField->setValue($this->getValueArray());
        $valueField->setForm($this->form);

        return $field->Field() . $valueField->Field();
    }
}
