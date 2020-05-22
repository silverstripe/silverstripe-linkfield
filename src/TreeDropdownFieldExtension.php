<?php

namespace SilverStripe\Link;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\Link;

class TreeDropdownFieldExtension extends Extension
{

    public function updateLink(&$link, $action)
    {
        /** @var TreeDropdownField $owner */
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
