<?php
namespace SilverStripe\Link\Type;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphqlType;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\QueryCreator as GraphqlQueryCreator;

/**
 * GraphQL Query to retrieve usage count for files and folders on GraphQL request.
 */
class QueryCreator extends GraphqlQueryCreator
{

    public function attributes()
    {
        return [
            'name' => 'readLinkTypes'
        ];
    }

    public function type()
    {
        return GraphqlType::listOf($this->manager->getType('LinkType'));
    }

    public function args()
    {
        return [
            'keys' => [
                'type' => GraphqlType::listOf(GraphqlType::id()),
            ],
        ];
    }

    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        if (isset($args['keys']) && !is_array($args['keys'])) {
            throw new \InvalidArgumentException('If `keys` is provdied, it must be an array');
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
