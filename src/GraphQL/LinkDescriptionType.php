<?php

namespace SilverStripe\Link\GraphQL;

use GraphQL\Type\Definition\Type as GraphqlType;
use SilverStripe\GraphQL\TypeCreator as GraphqlTypeCreator;

/**
 * GraphQL type for serving a Link Description.
 */
class LinkDescriptionType extends GraphqlTypeCreator
{
    /**
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'LinkDescription',
            'description' => 'Given some Link data, computes the matching description',
        ];
    }

    /**
     * @return array
     */
    public function fields()
    {
        return [
            'description' => [
                'type' => GraphqlType::string(),
            ],
        ];
    }
}
