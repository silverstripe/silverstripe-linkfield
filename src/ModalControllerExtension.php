<?php

namespace SilverStripe\Link;

use SilverStripe\Admin\ModalController;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;
use SilverStripe\Link\Type\Registry;
use SilverStripe\View\Requirements;

class ModalControllerExtension extends Extension
{
    private static $url_handlers = [
        'editorAnchorLink/$ItemID' => 'editorAnchorLink', // Matches LeftAndMain::methodSchema args
    ];

    private static $allowed_actions = array(
        'DynamicLink',
    );

    /**
     * Builds and returns the external link form
     *
     * @return Form
     */
    public function DynamicLink()
    {
        // Show link text field if requested
        $linkDataJsonStr = $this->getOwner()->controller->getRequest()->getVar('data');

        /** @var ModalController $owner */
        $owner = $this->getOwner();

        $factory = FormFactory::singleton();
        return $factory->getForm(
            $owner->getController(),
            "{$owner->getName()}/DynamicLink",
            $this->getContext()
        )->loadDataFrom($this->getData());
    }

    private function getContext()
    {
        $linkTypeKey = $this->getOwner()->controller->getRequest()->getVar('key');
        if (empty($linkTypeKey)) {
            throw new HTTPResponse_Exception(sprintf('key is required', __CLASS__), 400);
        }

        $type = Registry::singleton()->byKey($linkTypeKey);

        if (empty($type)) {
            throw new HTTPResponse_Exception(sprintf('%s is not a valid link type', 400));
        }

        return [
            'LinkData' => $this->getData(),
            'LinkType' => $type,
            'LinkTypeKey' => $linkTypeKey,
            'RequireLinkText' => false
        ];
    }

    private function getData()
    {
        $data = [];
        if ($dataString = $this->getOwner()->controller->getRequest()->getVar('data')) {
            $parsedData = json_decode($dataString, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $parsedData;
            }
        }

        return $data;
    }
}
