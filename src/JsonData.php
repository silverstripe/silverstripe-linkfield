<?php declare(strict_types=1);

namespace SilverStripe\LinkField;

use JsonSerializable;

/**
 * An object that can be serialized and deserialized to JSON.
 *
 * @deprecated 3.0.0 Will be removed without equivalent functionality to replace it
 */
interface JsonData extends JsonSerializable
{
    /**
     * @param array|JsonData $data
     * @return $this
     */
    public function setData($data): self;
}
