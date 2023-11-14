<?php

namespace SilverStripe\LinkField\Controllers;

use SilverStripe\Admin\AdminRootController;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\DefaultFormFactory;
use SilverStripe\Forms\Form;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Type\Registry;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\ORM\ValidationException;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\HiddenField;

class LinkFieldController extends LeftAndMain
{
    public const FORM_NAME_TEMPLATE = 'LinkForm_%s';

    private static $url_segment = 'linkfield';

    private static $url_handlers = [
        'linkForm/$ItemID' => 'linkForm',
        'GET data/$ItemID' => 'linkData',
        'DELETE delete/$ItemID' => 'linkDelete',
    ];

    private static $allowed_actions = [
        'linkForm',
        'linkData',
        'linkDelete',
    ];

    public function getClientConfig()
    {
        $clientConfig = parent::getClientConfig();
        $clientConfig['form']['linkForm'] = [
            // schema() is defined on LeftAndMain
            // schemaUrl will get the $ItemID and ?typeKey dynamically suffixed in LinkModal.js
            'schemaUrl' => $this->Link('schema/linkForm'),
            'deleteUrl' => $this->Link('delete'),
            'dataUrl' => $this->Link('data'),
            'saveMethod' => 'post',
            'formNameTemplate' => sprintf(self::FORM_NAME_TEMPLATE, '{id}'),
        ];
        return $clientConfig;
    }

    /**
     * Used for both:
     * - GET requests to get the FormSchema via `getLinkForm()` called from LeftAndMain::schema()
     * - POST Requests to save the Form. Will be handled by to FormRequestHandler::httpSubmission()
     * /admin/linkfield/linkForm/<LinkID>
     */
    public function linkForm(): Form
    {
        $id = (int) $this->itemIDFromRequest();
        if ($id) {
            $link = Link::get()->byID($id);
            if (!$link) {
                $this->jsonError(404, _t('LinkField.INVALID_ID', 'Invalid ID'));
            }
            $operation = 'edit';
            if (!$link->canView()) {
                $this->jsonError(403, _t('LinkField.UNAUTHORIZED', 'Unauthorized'));
            }
        } else {
            $typeKey = $this->typeKeyFromRequest();
            $link = Registry::create()->byKey($typeKey);
            if (!$link) {
                $this->jsonError(404, _t('LinkField.INVALID_TYPEKEY', 'Invalid typeKey'));
            }
            $operation = 'create';
        }
        return $this->createLinkForm($link, $operation);
    }

    /**
     * Get data for a Link
     * /admin/linkfield/data/<LinkID>
     */
    public function linkData(): HTTPResponse
    {
        $link = $this->linkFromRequest();
        if (!$link->canView()) {
            $this->jsonError(403, _t('LinkField.UNAUTHORIZED', 'Unauthorized'));
        }
        $response = $this->getResponse();
        $response->addHeader('Content-type', 'application/json');
        $data = $link->jsonSerialize();
        $data['description'] = $link->getDescription();
        $response->setBody(json_encode($data));
        return $response;
    }

    /**
     * Delete a Link
     * /admin/linkfield/delete/<LinkID>
     */
    public function linkDelete(): HTTPResponse
    {
        $link = $this->linkFromRequest();
        if (!$link->canDelete()) {
            $this->jsonError(403, _t('LinkField.UNAUTHORIZED', 'Unauthorized'));
        }
        // Check security token on destructive operation
        if (!SecurityToken::inst()->checkRequest($this->getRequest())) {
            $this->jsonError(400, _t('LinkField.INVALID_TOKEN', 'Invalid CSRF token'));
        }
        // delete() will also delete any published version immediately
        $link->delete();
        $response = $this->getResponse();
        $response->addHeader('Content-type', 'application/json');
        $response->setBody(json_encode(['success' => true]));
        return $response;
    }

    /**
     * This method is called from LeftAndMain::schema()
     * /admin/linkfield/schema/linkForm/<LinkID>
     */
    public function getLinkForm(): Form
    {
        return $this->linkForm();
    }

    /**
     * Arrive here from FormRequestHandler::httpSubmission() during a POST request to
     * /admin/linkfield/linkForm/<LinkID>
     * The 'save' method is called because it is the FormAction set on the Form
     */
    public function save(array $data, Form $form): HTTPResponse
    {
        if (empty($data)) {
            $this->jsonError(400, _t('LinkField.EMPTY_DATA', 'Empty data'));
        }

        /** @var Link $link */
        $id = (int) $this->itemIDFromRequest();
        if ($id) {
            // Editing an existing Link
            $operation = 'edit';
            $link = Link::get()->byID($id);
            if (!$link) {
                $this->jsonErorr(404, _t('LinkField.INVALID_ID', 'Invalid ID'));
            }
            if (!$link->canEdit()) {
                $this->jsonError(403, _t('LinkField.UNAUTHORIZED', 'Unauthorized'));
            }
        } else {
            // Creating a new Link
            $operation = 'create';
            $typeKey = $this->typeKeyFromRequest();
            $className = Registry::create()->list()[$typeKey] ?? '';
            if (!$className) {
                $this->jsonError(404, _t('LinkField.INVALID_TYPEKEY', 'Invalid typeKey'));
            }
            $link = $className::create();
            if (!$link->canCreate()) {
                $this->jsonError(403, _t('LinkField.UNAUTHORIZED', 'Unauthorized'));
            }
        }

        // Ensure that ItemID url param matches the ID in the form data
        if (isset($data['ID']) && ((int) $data['ID'] !== $id)) {
            $this->jsonError(400, _t('LinkField.BAD_DATA', 'Bad data'));
        }

        // Update DataObject from form data
        $form->saveInto($link);

        // Special logic for FileLink
        if (is_a($link, FileLink::class)) {
            // FileField value will come in as $postVars['File']['Files'][0];
            // $form->saveInto($link); doesn't seem to handle this
            $link->FileID = $data['File']['Files'][0] ?? 0;
        }

        // DataObject validation
        // thrown ValidationException will be caught in FormRequestHandler::httpSubmission()
        // Note: Form (as opposed to DataObject) validate() is run in FormRequestHandler::httpSubmission()
        $validationResult = $link->validate();
        if (!$validationResult->isValid()) {
            throw ValidationException::create($validationResult);
        }

        // Write to the database if the DataObject has changed
        if ($link->isChanged()) {
            $link->write();
        }

        // Create a new Form so that it has the correct ID for the DataObject when creating
        // a new DataObject, as well as anything else on the DataObject that may have been
        // updated in an extension hook. We do this so that the FormSchema state is correct
        // before returning it in the response
        $form = $this->createLinkForm($link, $operation);

        // Create and send FormSchema JSON response
        $schemaID = $form->FormAction();
        $response = $this->getSchemaResponse($schemaID, $form, $validationResult);
        return $response;
    }


    /**
     * Create the Form used to content manage a Link in a modal
     */
    private function createLinkForm(Link $link, string $operation): Form
    {
        $id = $link->ID;

        // Create the form
        $formFactory = Injector::inst()->get(DefaultFormFactory::class);
        $name = sprintf(self::FORM_NAME_TEMPLATE, $id);
        /** @var Form $form */
        $form = $formFactory->getForm($this, $name, ['Record' => $link]);

        // Set where the form is submitted to
        $typeKey = Registry::create()->keyByClassName($link->ClassName);
        $form->setFormAction($this->Link("linkForm/$id?typeKey=$typeKey"));

        // Add save action button
        $title = $id
                ? _t('LinkField.UPDATE_LINK', 'Update link')
                : _t('LinkField.CREATE_LINK', 'Create link');
        $actions = FieldList::create([
            FormAction::create('save', $title)
                ->setSchemaData(['data' => ['buttonStyle' => 'primary']]),
        ]);
        $form->setActions($actions);

        // Set the form request handler to return a FormSchema response during a POST request
        // This will override the default FormRequestHandler::getAjaxErrorResponse() which isn't useful
        $form->setValidationResponseCallback(function (ValidationResult $errors) use ($form, $id) {
            $schemaId = Controller::join_links(
                $this->Link('schema'),
                $this->config()->get('url_segment'),
                $id
            );
            return $this->getSchemaResponse($schemaId, $form, $errors);
        });

        // Make readonly if fail can check
        if ($operation === 'create' && !$link->canCreate()
            || $operation === 'edit' && !$link->canEdit()) {
            $form->makeReadonly();
        }

        // Styling
        $form->addExtraClass('form--no-dividers');

        return $form;
    }

    /**
     * Get a Link object based on the $ItemID request param
     */
    private function linkFromRequest(): Link
    {
        $itemID = (int) $this->itemIDFromRequest();
        if (!$itemID) {
            $this->jsonError(404, _t('LinkField.INVALID_ID', 'Invalid ID'));
        }
        $link = Link::get()->byID($itemID);
        if (!$link) {
            $this->jsonError(404, _t('LinkField.INVALID_ID', 'Invalid ID'));
        }
        return $link;
    }

    /**
     * Get the $ItemID request param
     */
    private function itemIDFromRequest(): string
    {
        $request = $this->getRequest();
        $itemID = (string) $request->param('ItemID');
        if (!ctype_digit($itemID)) {
            $this->jsonError(404, _t('LinkField.INVALID_ID', 'Invalid ID'));
        }
        return $itemID;
    }

    /**
     * Get the ?typeKey request querystring param
     */
    private function typeKeyFromRequest(): string
    {
        $request = $this->getRequest();
        $typeKey = (string) $request->getVar('typeKey');
        if (strlen($typeKey) === 0 || !preg_match('#^[a-z\-]+$#', $typeKey)) {
            $this->jsonError(404, _t('LinkField.INVALID_TYPEKEY', 'Invalid typeKey'));
        }
        return $typeKey;
    }
}
