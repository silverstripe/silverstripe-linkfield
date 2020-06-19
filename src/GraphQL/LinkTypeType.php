<?php

namespace SilverStripe\Link\GraphQL;

use GraphQL\Type\Definition\Type as GraphqlType;
use SilverStripe\GraphQL\TypeCreator as GraphqlTypeCreator;

/**
 * Provide a GraphQL Type for LinkTypes
 */
class LinkTypeType extends GraphqlTypeCreator
{
    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'LinkType',
            'description' => 'Describe a Type of Link that can be managed by a LinkField',
        ];
    }

    /**
     * @return array
     */
    public function fields()
    {
        return [
            'key' => [
                'type' => GraphqlType::nonNull(GraphqlType::id()),
            ],
            'handlerName' => [
                'type' => GraphqlType::nonNull(GraphqlType::string()),
            ],
            'title' => [
                'type' => GraphqlType::nonNull(GraphqlType::string()),
            ],
        ];
    }
}
