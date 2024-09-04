<?php

namespace SilverStripe\LinkField\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Extensions\TopPageElementExtension;
use DNADesign\Elemental\Extensions\TopPageFluentElementExtension;
use SilverStripe\Core\Extension;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\ORM\DataObject;

class UsedOnTableExtension extends Extension
{
    protected function updateUsageAncestorDataObjects(array &$ancestorDataObjects, DataObject $dataObject): void
    {
        if (!is_a($dataObject, FileLink::class)) {
            return;
        }
        $owner = $dataObject->Owner();
        if (!$owner?->exists()) {
            return;
        }
        $ancestorDataObjects[] = $owner;
        if (!class_exists(BaseElement::class) || !is_a($owner, BaseElement::class)) {
            return;
        }
        if ($owner->hasExtension(TopPageElementExtension::class)) {
            $page = $owner->getTopPage();
        } else {
            $page = $owner->getPage();
        }
        if (!$page?->exists()) {
            return;
        }
        $ancestorDataObjects[] = $page;
    }
}
