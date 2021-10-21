<?php declare(strict_types=1);

namespace SilverStripe\Link;

use JsonSerializable;

/**
 * An object that can be serialize and deserialize to JSON.
 */
interface JsonData extends JsonSerializable
{

    /**
     * @param array|JsonData $data
     * @return $this
     */
    public function setData($data): self;
}
