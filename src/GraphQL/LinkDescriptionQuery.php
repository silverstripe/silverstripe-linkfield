<?php
namespace SilverStripe\Link\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphqlType;
use SilverStripe\GraphQL\QueryCreator;
use SilverStripe\Link\Type\Registry;

/**
 * GraphQL Query to generate a Link Description for a provided JSON string representing the Link Data.
 */
class LinkDescriptionQuery extends QueryCreator
{

    public function attributes()
    {
        return [
            'name' => 'readLinkDescription'
        ];
    }

    public function type()
    {
        return $this->manager->getType('LinkDescription');
    }

    public function args()
    {
        return [
            'dataStr' => [
                'type' => GraphqlType::nonNull(GraphqlType::string()),
            ],
        ];
    }

    public function resolve($object, array $args, $context, ResolveInfo $info)
    {
        $data = json_decode($args['dataStr'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('data must be a valid JSON string');
        }

        if (empty($data['typeKey'])) {
            return ['description' => ''];
        }

        $type = Registry::singleton()->byKey($data['typeKey']);
        if (empty($type)) {
            return ['description' => ''];
        }


        return ['description' => $type->generateLinkDescription($data)];
    }
}
