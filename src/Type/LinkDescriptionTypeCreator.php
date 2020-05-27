<?php

namespace SilverStripe\Link\Type;

use GraphQL\Type\Definition\Type as GraphqlType;
use SilverStripe\GraphQL\TypeCreator as GraphqlTypeCreator;
use GraphQL\Type\Definition\ResolveInfo;

/**
 *
 */
class LinkDescriptionTypeCreator extends GraphqlTypeCreator
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
