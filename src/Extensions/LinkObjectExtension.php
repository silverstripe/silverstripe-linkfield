<?php

namespace SilverStripe\LinkField\Extensions;

use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;

/**
 * This is only intended to be added to Link and LinkArea
 * Implemented as an extension rather than base class so it doesn't create an extra base table that needs to be joined
 */
class LinkObjectExtension extends DataExtension
{
    private static array $db = [
        'OwnerID' => 'Int',
        'OwnerClassName' => 'Varchar',
    ];

    public function canView($member = null)
    {
        return $this->canCheck('canView', $member);
    }

    public function canCreate($member = null)
    {
        return $this->canCheck('canCreate', $member);
    }

    public function canEdit($member = null)
    {
        return $this->canCheck('canEdit', $member);
    }

    public function canDelete($member = null)
    {
        return $this->canCheck('canDelete', $member);
    }

    private function canCheck(string $canMethod, $member)
    {
        if (!$member) {
            $member = Security::getCurrentUser();
        }
        $extended = $this->extendedCan($canMethod, $member);
        if ($extended !== null) {
            return $extended;
        }
        $owner = $this->getOwningDataObject();
        if ($owner) {
            return $owner->$canMethod($member);
        }
        return parent::$canMethod($member);
    }

    private function getOwningDataObject(): ?DataObject
    {
        // Thismethod is not called getOwner() because of existing Extension::getOwner() method
        //
        // If this is a Link, and LinkArea is set on it return that
        if (is_a($this->owner, Link::class, true)) {
            $linkArea = $this->owner->LinkArea();
            if ($linkArea && $linkArea->exists()) {
                return $linkArea;
            }
        }
        // Otherwise look for the ownerID + ownerClassName
        // These are set in DataObjectExtension::onAfterWrite()
        $ownerID = $this->owner->OwnerID;
        $ownerClassName = $this->owner->OwnerClassName;
        if ($ownerID === 0 || $ownerClassName === '') {
            return null;
        }
        return $ownerClassName::get()->byID($ownerID);
    }
}
