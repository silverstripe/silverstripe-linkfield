<?php

namespace SilverStripe\LinkField\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use SilverStripe\GraphQL\Schema\DataObject\Resolver;
use SilverStripe\LinkField\Type\Registry;
use SilverStripe\Dev\Deprecation;

/**
 * @deprecated 3.0.0 Will be removed without equivalent functionality to replace it
 */
class LinkDescriptionResolver extends Resolver
{
    public function __construct()
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed without equivalent functionality to replace it', Deprecation::SCOPE_CLASS);
        });
    }

    public static function resolve($obj, $args = [], $context = [], ?ResolveInfo $info = null)
    {
        $data = json_decode($args['dataStr'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('data must be a valid JSON string');
        }

        $typeKey = $data['typeKey'] ?? null;

        if (!$typeKey) {
            return ['description' => ''];
        }

        $type = Registry::singleton()->byKey($typeKey);

        if (!$type) {
            return ['description' => ''];
        }

        return ['description' => $type->generateLinkDescription($data)];
    }
}
