<?php

namespace SilverStripe\LinkField\Form\Traits;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Services\LinkTypeService;

/**
 * Trait to manage which Link type can be added to LinkField form field.
 * This trait is used in LinkField and MultiLinkField classes.
 */
trait AllowedLinkClassesTrait
{
    private array $allowed_types = [];

    /**
     * Set allowed types for LinkField
     * @param string[] $types
     */
    public function setAllowedTypes(array $types): static
    {
        if ($this->validateTypes($types)) {
            $this->allowed_types = $types;
        }

        return $this;
    }

    /**
     * Get allowed types for LinkField
     */
    public function getAllowedTypes(): array
    {
        return $this->allowed_types;
    }

    /**
     * Validate types that they are subclasses of Link
     * @param string[] $types
     * @throws InvalidArgumentException
     */
    private function validateTypes(array $types): bool
    {
        if (empty($types)) {
            throw new InvalidArgumentException(
                _t(
                    __TRAIT__ . '.INVALID_TYPECLASS_EMPTY',
                    '"{class}": Allowed types cannot be empty',
                    ['class' => static::class],
                ),
            );
        }

        $validClasses = [];
        foreach ($types as $type) {
            if (is_subclass_of($type, Link::class)) {
                $validClasses[] = $type;
            } else {
                throw new InvalidArgumentException(
                    _t(
                        __TRAIT__ . '.INVALID_TYPECLASS',
                        '"{class}": {typeclass} is not a valid Link Type',
                        ['class' => static::class, 'typeclass' => $type],
                        sprintf(
                            '"%s": %s is not a valid Link Type',
                            static::class,
                            $type,
                        ),
                    ),
                );
            }
        }

        return count($validClasses) > 0;
    }

    /**
     * The method returns an associational array converted to a JSON string,
     * of available link types with additional parameters necessary
     * for full-fledged work on the client side.
     * @throws InvalidArgumentException
     */
    public function getTypesProps(): string
    {
        $typesList = [];
        $typeDefinitions = $this->genarateAllowedTypes();
        foreach ($typeDefinitions as $key => $class) {
            $type = Injector::inst()->get($class);
            if (!$type->canCreate()) {
                continue;
            }
            $typesList[$key] = [
                'key' => $key,
                'title' => $type->i18n_singular_name(),
                'handlerName' => $type->LinkTypeHandlerName(),
            ];
        }

        return json_encode($typesList);
    }

    /**
     * Generate allowed types with key => value pair
     * Example: ['cms' => SiteTreeLink::class]
     * @param string[] $types
     */
    private function genarateAllowedTypes(): array
    {
        $typeDefinitions = $this->getAllowedTypes() ?? [];

        if (empty($typeDefinitions)) {
            return LinkTypeService::create()->generateAllLinkTypes();
        }

        $result = array();
        foreach ($typeDefinitions as $class) {
            if (is_subclass_of($class, Link::class)) {
                $type = Injector::inst()->get($class)->getShortCode();
                $result[$type] = $class;
            }
        }

        return $result;
    }
}
