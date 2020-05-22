<?php

namespace SilverStripe\Link;

use SilverStripe\Core\Extension;
use SilverStripe\Link\Type\Registry;
use SilverStripe\View\Requirements;

class LeftAndMainExtension extends Extension
{
    public function init()
    {
        Registry::singleton()->init();
    }

    public function updateClientConfig(&$clientConfig)
    {
        $clientConfig['form']['DynamicLink'] = [
            'schemaUrl' => $this->getOwner()->Link('methodSchema/Modals/DynamicLink'),
        ];
    }

    public function DynamicLink()
    {

    }

}
