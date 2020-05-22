<?php

namespace SilverStripe\Link\Type;

use InvalidArgumentException;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;

/**
 * Manage the list of known link types
 */
class Registry
{
    use Configurable;
    use Extensible;
    use Injectable;

    private static $types = [];


    /**
     * Find the matching LinkType by its key or null it can't be found.
     * @param string $key
     * @return Type|null
     * @throws InvalidArgumentException
     */
    public function byKey(string $key): ?Type
    {
        /** @var array $types */
        $typeDefinitions = self::config()->get('types');
        if (empty($typeDefinitions[$key])) {
            return null;
        }

        return $this->definitionToType($typeDefinitions[$key]);
    }

    /**
     * @return Type[]
     * @throws InvalidArgumentException
     */
    public function list(): array
    {
        /** @var Type[] $types */
        $types = [];

        /** @var array $types */
        $typeDefinitions = self::config()->get('types');

        foreach ($typeDefinitions as $key => $def) {
            $types[$key] = $this->definitionToType($def);
        }

        return $types;
    }

    /**
     * @return string[]
     */
    public function keys(): array
    {

    }

    /**
     * @return string[]
     */
    public function keysEnabledByDefault(): array
    {

    }

    public function init()
    {
        foreach ($this->list() as $type)
        {
            $type->defineLinkTypeRequirements();
        }
    }

    /**
     * @param array $def
     * @throws LogicException
     */
    private function definitionToType(array $def): Type
    {
        if (empty($def['classname'])) {
            throw new \LogicException(sprintf('%s: All types should reference a valid classname', __CLASS__));
        }

        /** @var Type $type */
        $type = Injector::inst()->get($def['classname']);

        if (!$type instanceof Type) {
            throw new \LogicException(sprintf('%s: %s is not a valid link type', __CLASS__, $def['classname']));
        }

        return $type;
    }

    public function keyByClassName(string $classname): ?string
    {
        $typeDefinitions = self::config()->get('types');
        foreach ($typeDefinitions as $key => $def) {
            if ($def['classname'] === $classname) {
                return $key;
            }
        }

        return null;
    }
}
