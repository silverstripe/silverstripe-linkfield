<?php

namespace SilverStripe\LinkField\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\LinkField\Type\Registry;
use SilverStripe\Dev\Deprecation;

/**
 * Register a new Form Schema in LeftAndMain.
 *
 * @deprecated 3.0.0 Will be removed without equivalent functionality to replace it
 */
class LeftAndMain extends Extension
{
    public function __construct()
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed without equivalent functionality to replace it', Deprecation::SCOPE_CLASS);
        });
        parent::__construct();
    }

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
}
