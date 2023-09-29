<?php

namespace SilverStripe\LinkField\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\LinkField\Type\Registry;

/**
 * Endpoint for /admin/linkfield/<action>
 *
 * Routing to this controller is done via Extensions/LeftAndMain.php in the linkfield() method
 */
class LinkFieldController extends Controller
{
    private static array $allowed_actions = [
        'description',
        'types',
    ];

    /**
     * Used for requests to /admin/linkfield/description?data={json}
     */
    public function description(HTTPRequest $request): HTTPResponse
    {
        $jsonStr = $request->getVar('data');
        // data will be an empty array if there is no existing link which is a valid use case
        $data = json_decode($jsonStr, true);
        $description = '';
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new HTTPResponse_Exception('data is not a valid JSON string');
        }
        if (array_key_exists('typeKey', $data)) {
            $typeKey = $data['typeKey'];
            /** @var Type $type */
            $type = Registry::singleton()->byKey($typeKey);
            if (!$type) {
                throw new HTTPResponse_Exception('typeKey is not allowed');
            }
            $description = $type->generateLinkDescription($data);
        }
        return $this->jsonResponse([
            'description' => $description
        ]);
    }

    /**
     * Used for requests to /admin/linkfield/types
     */
    public function types(): HTTPResponse
    {
        $data = [];
        /** @var Type $type */
        foreach (Registry::singleton()->list() as $key => $type) {
            $data[$key] = [
                'key' => $key,
                'handlerName' => $type->LinkTypeHandlerName(),
                'title' => $type->LinkTypeTile()
            ];
        }
        return $this->jsonResponse($data);
    }

    /**
     * Create a JSON response to send back to the browser
     */
    private function jsonResponse(array $data)
    {
        $response = $this->getOwner()->getResponse();
        $jsonStr = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $response->setBody($jsonStr);
        $response->addHeader('Content-type', 'application/json');
        return $response;
    }
}
