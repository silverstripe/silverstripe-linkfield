<?php

namespace SilverStripe\LinkField\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use SilverStripe\GraphQL\Schema\DataObject\Resolver;
use SilverStripe\LinkField\Type\Registry;
use SilverStripe\LinkField\Models\Link;

class LinkTypeResolver extends Resolver
{
    public static function resolve($obj, $args = [], $context = [], ?ResolveInfo $info = null)
    {
        if (isset($args['keys']) && !is_array($args['keys'])) {
            throw new InvalidArgumentException(_t('LinkField.KEYS_ARE_NOT_ARRAY', 'If `keys` is provdied, it must be an array'));
        }

        $types = Registry::singleton()->list();
        $flattenType = array_map(function (Link $type, string $key) {
            return [
                'key' => $key,
                'title' => $type->LinkTypeTile(),
                'handlerName' => $type->LinkTypeHandlerName(),
            ];
        }, $types, array_keys($types));

        return $flattenType;
    }
}
