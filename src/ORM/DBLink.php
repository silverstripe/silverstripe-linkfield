<?php

namespace SilverStripe\Link\ORM;

use SilverStripe\Link\Type\Registry;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Link\Form\LinkField;

/**
 * Represent Link object stored as a JSON string
 */
class DBLink extends DBJson
{
    /**
     * Load the link data into a singleton Link Object
     *
     * @return DBHTMLText
     */
    public function forTemplate()
    {
        $value = $this->getValue();
        if ($value) {
            $type = Registry::singleton()->byKey($value['typeKey']);
            if ($type) {
                return $type->loadLinkData($value)->forTemplate();
            }
        }
    }

    public function scaffoldFormField($title = null, $params = null)
    {
        return LinkField::create($this->getName(), $this->getValue());
    }
}
