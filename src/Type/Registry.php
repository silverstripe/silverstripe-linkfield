<?php

namespace SilverStripe\LinkField\Type;

use InvalidArgumentException;
use LogicException;
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
        $definition = $typeDefinitions[$key] ?? null;

        if (!$definition) {
            return null;
        }

        return $this->definitionToType($definition);
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
            // This link type is disabled, so we can skip it
            if (!array_key_exists('enabled', $def) || !$def['enabled']) {
                continue;
            }

            $types[$key] = $this->definitionToType($def);
        }

        return $types;
    }

    /**
     * @return string[]
     */
    public function keys(): array
    {
        return [];
    }

    /**
     * @return string[]
     */
    public function keysEnabledByDefault(): array
    {
        return [];
    }

    public function init()
    {
        foreach ($this->list() as $type) {
            $type->defineLinkTypeRequirements();
        }
    }

    /**
     * @param array $def
     * @throws LogicException
     */
    private function definitionToType(array $def): Type
    {
        $className = $def['classname'] ?? null;

        if (!$className) {
            throw new LogicException(sprintf('%s: All types should reference a valid classname', static::class));
        }

        /** @var Type $type */
        $type = Injector::inst()->get($className);

        if (!$type instanceof Type) {
            throw new LogicException(sprintf('%s: %s is not a valid link type', static::class, $className));
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
