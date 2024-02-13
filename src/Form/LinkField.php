<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Form;

use SilverStripe\LinkField\Models\Link;
use SilverStripe\Forms\HasOneRelationFieldInterface;

/**
 * A react-based formfield which allows CMS users to edit a Link record.
 */
class LinkField extends AbstractLinkField implements HasOneRelationFieldInterface
{
    public function setValue(mixed $value, $data = null): static
    {
        if (is_a($value, Link::class)) {
            $id = $value->ID;
        } else {
            $id = $value;
        }
        return parent::setValue($id, $data);
    }
}
