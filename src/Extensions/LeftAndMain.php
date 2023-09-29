<?php

namespace SilverStripe\LinkField\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\LinkField\Type\Registry;
use SilverStripe\LinkField\Controllers\LinkFieldController;

/**
 * Register a new Form Schema in LeftAndMain.
 */
class LeftAndMain extends Extension
{
    private static $allowed_actions = [
        'linkfield',
    ];

    public function init()
    {
        // Get the Link Registry to load all the JS requirements for managing Links.
        Registry::singleton()->init();
    }

    public function updateClientConfig(&$clientConfig)
    {
        $clientConfig['form']['DynamicLink'] = [
            'schemaUrl' => $this->getOwner()->Link('methodSchema/Modals/DynamicLink'),
        ];
    }

    /**
     * Route requests to /admin/linkfield to LinkFieldController
     */
    public function linkfield(): LinkFieldController
    {
        return LinkFieldController::create();
    }
}
