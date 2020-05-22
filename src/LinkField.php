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

    protected function parseString(string $value): ?JsonData
    {
        var_dump($value);

        $data = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(sprintf('%s: Could not parse provided JSON string', __CLASS__));
        }

        if (empty($data)) {
            return null;
        }

        if (empty($data['typeKey'])) {
            throw new InvalidArgumentException(sprintf('%s: Link Data must contain a typeKey', __CLASS__));
        }

        $type = Registry::singleton()->byKey($data['typeKey']);

        var_dump($data);

        if (!$type) {
            throw new InvalidArgumentException(sprintf('%s: Could not find a matching link type for %s', __CLASS__, $data['typeKey']));
        }

        $link = $type->loadLinkData($data);

        var_dump($link);

        $link->update(['Title' => 'seniorita']);

        var_dump($link);

        die();

        return $type->loadLinkData($data);
    }


}
