<?php

namespace SilverStripe\LinkField\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use SilverStripe\GraphQL\Schema\DataObject\Resolver;
use SilverStripe\LinkField\Type\Registry;
use SilverStripe\LinkField\Type\Type;
use SilverStripe\Dev\Deprecation;

/**
 * @deprecated 3.0.0 Will be removed without equivalent functionality to replace it
 */
class LinkTypeResolver extends Resolver
{
    public function __construct()
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed without equivalent functionality to replace it', Deprecation::SCOPE_CLASS);
        });
    }

    public static function resolve($obj, $args = [], $context = [], ?ResolveInfo $info = null)
    {
        if (isset($args['keys']) && !is_array($args['keys'])) {
            throw new InvalidArgumentException('If `keys` is provdied, it must be an array');
        }

        $types = Registry::singleton()->list();
        $flattenType = array_map(function (Type $type, string $key) {
            return [
                'key' => $key,
                'handlerName' => $type->LinkTypeHandlerName(),
                'title' => $type->LinkTypeTile()
            ];
        }, $types, array_keys($types));

        return $flattenType;
    }
}
