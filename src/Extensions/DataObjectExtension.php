<?php

namespace SilverStripe\LinkField\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Models\LinkArea;

class DataObjectExtension extends DataExtension
{
    public function onAfterWrite(): void
    {
        // Using onAfterWrite instead of onBeforeWrite to ensure that $this->owner->ID is not zero when creating new objects
        parent::onAfterWrite();
        foreach ($this->owner->hasOne() as $relation => $relationClassName) {
            $relationField = $relation . 'ID';
            $relationID = $this->owner->$relationField;
            if (!$relationID) {
                continue;
            }
            if (!is_a($relationClassName, Link::class, true) && !is_a($relationClassName, LinkArea::class, true)) {
                continue;
            }
            // skip for the has_one:LinkArea relation on Link
            if (is_a($this->owner, Link::class) && $relation === 'LinkArea') {
                continue;
            }
            $relationObj = $relationClassName::get()->byID($relationID);
            if ($relationObj === null) {
                // could throw an Exception here, though not sure how if user would be able to fix it
                continue;
            }
            $doWrite = false;
            if ($relationObj->OwnerID !== $this->owner->ID) {
                $relationObj->OwnerID = $this->owner->ID;
                $doWrite = true;
            }
            if ($relationObj->OwnerClassName !== $this->owner->ClassName) {
                $relationObj->OwnerClassName = $this->owner->ClassName;
                $doWrite = true;
            }
            if ($doWrite) {
                $relationObj->write();
            }
        }
    }
}
