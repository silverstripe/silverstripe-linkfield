<?php

namespace SilverStripe\Link\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Schema\DataObject\Resolver;
use SilverStripe\Link\Type\Registry;

class LinkDescriptionResolver extends Resolver
{
    public static function resolve($obj, $args = [], $context = [], ?ResolveInfo $info = null)
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
