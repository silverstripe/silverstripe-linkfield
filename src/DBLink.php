<?php

namespace SilverStripe\Link;

use SilverStripe\Link\Type\Registry;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\ArrayData;

class DBLink extends DBJson
{
    /**
     * Return a rendered version of this form.
     *
     * This is returned when you access a form as $FormObject rather
     * than <% with FormObject %>
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
}
