<?php

namespace SilverStripe\LinkField\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\HasManyList;

/**
 * This extension must be applied to any DataObject which has a has_many relation to the Link model.
 */
class DataObjectWithLinksExtension extends Extension
{
    public function updateComponents(HasManyList &$list, string $relation)
    {
        if (is_a($list->dataClass(), Link::class, true)) {
            $list = $list->filter('OwnerRelation', $relation);
        }
    }
}
