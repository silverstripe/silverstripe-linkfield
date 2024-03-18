<?php

namespace SilverStripe\LinkField\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\FieldList;
use TractorCow\Fluent\Extension\FluentVersionedExtension;

class FluentLinkExtension extends Extension
{
    public function updateCMSFields(FieldList $fields)
    {
        $this->removeTabsWithFluentGridfields($fields);
    }

    /**
     * Remove tabs added by fluent that contain GridFields
     * This is done for because there is currently no react component for a GridFields
     * When using tractorcow/silverstripe-fluent tabs will be automatically added
     * that contain a GridFields which will cause LinkField modal to trigger a server error
     */
    private function removeTabsWithFluentGridfields(FieldList $fields): void
    {
        // Only remove tabs if there is no GridField react component specified
        $schemaDataType = GridField::create('tmp')->getSchemaDataType();
        if ($schemaDataType !== null) {
            return;
        }
        // Remove the tabs that contains the gridfields
        if ($this->getOwner()->hasExtension(FluentVersionedExtension::class)) {
            $fields->removeByName(['Locales', 'FilteredLocales']);
        }
    }
}
