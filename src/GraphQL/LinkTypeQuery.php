<?php
namespace SilverStripe\Link\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphqlType;
use SilverStripe\GraphQL\Pagination\Connection;
use SilverStripe\GraphQL\QueryCreator;
use SilverStripe\Link\Type\Registry;
use SilverStripe\Link\Type\Type;

/**
 * GraphQL Query to retrieve the list of possible LinkTypes.
 */
class LinkTypeQuery extends QueryCreator
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
