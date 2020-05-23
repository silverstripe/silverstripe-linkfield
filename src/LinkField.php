<?php declare(strict_types=1);

namespace SilverStripe\Link;

use InvalidArgumentException;
use SilverStripe\Forms\FormField;
use SilverStripe\Link\Type\Registry;

class LinkField extends JsonField
{
    protected $schemaComponent = 'LinkField';

    public function setValue($value, $data = null)
    {
        return parent::setValue($value, $data);
    }
}
