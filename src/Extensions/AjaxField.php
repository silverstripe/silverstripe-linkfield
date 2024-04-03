<?php

namespace SilverStripe\LinkField\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\FormField;
use SilverStripe\Dev\Deprecation;

/**
 * Tweak fields that need to be served through the DynamicLink form schema and need to be able to receive AJAX calls.
 *
 * For example the TreeDropdownField need to be able to receive AJAX request to fetch the list of available SiteTrees.
 *
 * This is a bit hackish. There's probably a less dumb way of doing this.
 *
 * @deprecated 3.0.0 Will be removed without equivalent functionality to replace it
 */
class AjaxField extends Extension
{
    public function __construct()
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed without equivalent functionality to replace it', Deprecation::SCOPE_CLASS);
        });
        parent::__construct();
    }

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
