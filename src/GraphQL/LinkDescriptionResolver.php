<?php

namespace SilverStripe\Link\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use SilverStripe\GraphQL\Schema\Resolver\DefaultResolverProvider;
use SilverStripe\Link\Type\Registry;

class LinkDescriptionResolver extends DefaultResolverProvider
{
    public static function resolve($object, array $args, $context, ResolveInfo $info)
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
