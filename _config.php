<?php

use SilverStripe\Admin\CMSMenu;
use SilverStripe\LinkField\Controllers\LinkFieldController;

CMSMenu::remove_menu_class(LinkFieldController::class);
