<?php

namespace SilverStripe\LinkField\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\View\Requirements;

class LeftAndMainExtension extends Extension
{
    public function init()
    {
        Requirements::add_i18n_javascript('silverstripe/linkfield:client/lang', false, true);
        Requirements::javascript('silverstripe/linkfield:client/dist/js/bundle.js');
        Requirements::css('silverstripe/linkfield:client/dist/styles/bundle.css');
    }
}
