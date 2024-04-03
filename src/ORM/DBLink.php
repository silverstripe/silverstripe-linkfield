<?php

namespace SilverStripe\LinkField\ORM;

use SilverStripe\LinkField\Type\Registry;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\Dev\Deprecation;

/**
 * Represent Link object stored as a JSON string
 *
 * @deprecated 3.0.0 Will be removed without equivalent functionality to replace it
 */
class DBLink extends DBJson
{
    public function __construct($name = null, $defaultVal = [])
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed without equivalent functionality to replace it', Deprecation::SCOPE_CLASS);
        });
        parent::__construct($name, $defaultVal);
    }

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
