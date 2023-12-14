<?php

namespace SilverStripe\LinkField\Services;

use InvalidArgumentException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\LinkField\Models\Link;

/**
 * This service class helps to obtain a list of all available Link subclasses,
 * an instance of the Link subclass by key, or a key by class name.
 */
class LinkTypeService
{
    use Injectable;

    /**
     * Generate all link types that are subclasses of Link::class
     */
    public function generateAllLinkTypes(): array
    {
        $typeDefinitions = ClassInfo::subclassesFor(Link::class);

        $result = array();
        foreach ($typeDefinitions as $class) {
            if (is_subclass_of($class, Link::class)) {
                $type = Injector::inst()->get($class)->getShortCode();
                $result[$type] = $class;
            }
        }

        return $result;
    }

    /**
     * Return a Link instance by key
     * @throws InvalidArgumentException
     */
    public function byKey(string $key): ?Link
    {
        $typeDefinitions = $this->generateAllLinkTypes();
        $className = $typeDefinitions[$key] ?? null;

        if (!$className) {
            return null;
        }

        return Injector::inst()->get($className);
    }

    /**
     * Return a key for link type by classname
     * @throws InvalidArgumentException
     */
    public function keyByClassName(string $classname): ?string
    {
        $typeDefinitions = $this->generateAllLinkTypes();

        foreach ($typeDefinitions as $key => $class) {
            if ($class === $classname) {
                return $key;
            }
        }

        return null;
    }
}
