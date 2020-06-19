<?php

namespace SilverStripe\Link\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;
use SilverStripe\Link;

/**
 * Tweak fields that need to be serve through the DynamicLink form schema and need to be able to receive AJAX calls.
 *
 * For example the TreeDropdownField need to be able to receive AJAX request to fetch the list of available SiteTrees.
 */
class AjaxField extends Extension
{

    public function updateLink(&$link, $action)
    {
        /** @var FormField $owner */
        $owner = $this->getOwner();
        $formName = $owner->getForm()->getName();

        if ($formName !== 'Modals/DynamicLink') {
            return;
        }

        $request = $owner->getForm()->getController()->getRequest();
        $key = $request->getVar('key');

        $link .= strpos($link, '?') === false ? '?' : '&';
        $link .= "key={$key}";
    }
}
