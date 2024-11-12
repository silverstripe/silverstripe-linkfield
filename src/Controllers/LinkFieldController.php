<?php

namespace SilverStripe\LinkField\Controllers;

use SilverStripe\Admin\FormSchemaController;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Forms\DefaultFormFactory;
use SilverStripe\Forms\Form;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\LinkField\Models\FileLink;
use SilverStripe\Core\Validation\ValidationException;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\HiddenField;
use SilverStripe\LinkField\Services\LinkTypeService;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\Versioned\Versioned;

class LinkFieldController extends FormSchemaController
{
    public const FORM_NAME_TEMPLATE = 'LinkForm_%s';

    private static string $url_segment = 'linkfield';

    private static array $url_handlers = [
        'linkForm/$ItemID' => 'linkForm',
        'GET data/$ItemID' => 'linkData',
        'DELETE delete/$ItemID' => 'linkDelete',
        'POST sort' => 'linkSort',
    ];

    private static array $allowed_actions = [
        'linkForm',
        'linkData',
        'linkDelete',
        'linkSort',
    ];

    private static string $required_permission_codes = 'CMS_ACCESS_CMSMain';

    public function getClientConfig(): array
    {
        $clientConfig = parent::getClientConfig();
        $clientConfig['form']['linkForm'] = [
            // schema() is defined on LeftAndMain
            // schemaUrl will get the $ItemID and ?typeKey dynamically suffixed in LinkModal.js
            // as well as ownerID, OwnerClass and OwnerRelation
            'schemaUrl' => $this->Link('schema/linkForm'),
            'deleteUrl' => $this->Link('delete'),
            'dataUrl' => $this->Link('data'),
            'sortUrl' => $this->Link('sort'),
            'saveMethod' => 'post',
            'formNameTemplate' => sprintf(LinkFieldController::FORM_NAME_TEMPLATE, '{id}'),
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
        $id = $this->itemIDFromRequest();
        if ($id) {
            $link = Link::get()->byID($id);
            if (!$link) {
                $this->jsonError(404);
            }
            $operation = 'edit';
            if (!$link->canView()) {
                $this->jsonError(403);
            }
        } else {
            $typeKey = $this->typeKeyFromRequest();
            $link = LinkTypeService::create()->byKey($typeKey);
            if (!$link) {
                $this->jsonError(404);
            }
            $operation = 'create';
        }
        $excludeLinkTextField = (bool) $this->getRequest()->getVar('excludeLinkTextField');
        return $this->createLinkForm($link, $operation, $excludeLinkTextField);
    }

    /**
     * Get data for a Link
     * /admin/linkfield/data/<LinkID>
     */
    public function linkData(HTTPRequest $request): HTTPResponse
    {
        $data = [];
        if ($request->param('ItemID')) {
            $link = $this->linkFromRequest();
            $data = $this->getLinkData($link);
        } else {
            $links = $this->linksFromRequest();
            foreach ($links as $link) {
                $data[$link->ID] = $this->getLinkData($link);
            }
        }
        return $this->jsonSuccess(200, $data);
    }

    private function getLinkData(Link $link): array
    {
        if (!$link->canView()) {
            $this->jsonError(403);
        }
        return $link->getData();
    }

    /**
     * Delete a Link
     * /admin/linkfield/delete/<LinkID>
     */
    public function linkDelete(): HTTPResponse
    {
        // Check security token on destructive operation
        if (!SecurityToken::inst()->checkRequest($this->getRequest())) {
            $this->jsonError(400);
        }
        $link = $this->linkFromRequest();
        if (!$link->canDelete()) {
            $this->jsonError(403);
        }
        if ($link->hasExtension(Versioned::class)) {
            $link->doArchive();
        } else {
            $link->delete();
        }
        // Send response
        return $this->jsonSuccess(204);
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
            $this->jsonError(400);
        }
        /** @var Link $link */
        $id = $this->itemIDFromRequest();
        if ($id) {
            // Editing an existing Link
            $operation = 'edit';
            $link = Link::get()->byID($id);
            if (!$link) {
                $this->jsonError(404);
            }
            if (!$link->canEdit()) {
                $this->jsonError(403);
            }
        } else {
            // Creating a new Link
            $operation = 'create';
            $typeKey = $this->typeKeyFromRequest();
            $className = LinkTypeService::create()->byKey($typeKey) ?? '';
            if (!$className) {
                $this->jsonError(404);
            }
            $link = $className::create();
            if (!$link->canCreate()) {
                $this->jsonError(403);
            }
        }

        // Ensure that ItemID url param matches the ID in the form data
        // Ensure that Sort is not being passed in - this is to prevent malicious users
        // from setting the Sort value to the maximum value of an integer, which would
        // cause the Sort field to overflow
        if ((isset($data['ID']) && ((int) $data['ID'] !== $id))
            || isset($data['Sort'])
        ) {
            $this->jsonError(400);
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

        // Update owner object if this Link is on a has_one relation on the owner
        // Only do this for has_one, not has_many, because that's stored directly on the Link record
        // Get owner using getOwnerFromRequest() rather than $link->Owner() so that validation is run
        // on the owner params before updating the database
        $owner = $this->getOwnerFromRequest();
        $ownerRelation = $this->getOwnerRelationFromRequest();
        $ownerRelationID = "{$ownerRelation}ID";
        $hasOne = Injector::inst()->get($owner->ClassName)->hasOne();
        if ($operation === 'create'
            && array_key_exists($ownerRelation, $hasOne)
            && $owner->$ownerRelationID !== $link->ID
            && $owner->canEdit()
        ) {
            $owner->$ownerRelation = $link;
            $owner->write();
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
     * Update Link Sort fields based frontend drag and drop sort logic
     * /admin/linkfield/sort
     */
    public function linkSort(): HTTPResponse
    {
        $request = $this->getRequest();
        // Check security token
        if (!SecurityToken::inst()->checkRequest($request)) {
            $this->jsonError(400);
        }
        $json = json_decode($request->getBody() ?? '');
        $newLinkIDs = $json?->newLinkIDs;
        // If someone's passing a JSON object or other non-array here, they're doing something wrong
        if (!is_array($newLinkIDs) || empty($newLinkIDs)) {
            $this->jsonError(400);
        }
        // Fetch and validate links
        $links = Link::get()->filter(['ID' => $newLinkIDs])->toArray();
        $linkIDToLink = [];
        $ownerID = null;
        $ownerRelation = null;
        foreach ($links as $link) {
            // Validate that all links are in the same relation with the same owner
            // This protects against malicious actors manually executing requests.
            if (is_null($ownerID)) {
                $ownerID = $link->OwnerID;
                $ownerRelation = $link->OwnerRelation;
            }
            if ($link->OwnerID !== $ownerID || $link->OwnerRelation !== $ownerRelation) {
                $this->jsonError(400);
            }
            $linkIDToLink[$link->ID] = $link;
        }
        // Check permissions on links that need to be updated
        foreach ($newLinkIDs as $i => $linkID) {
            $linkID = $newLinkIDs[$i];
            $link = $linkIDToLink[$linkID];
            // 'Sort has a minimum value of 1 as that's more standard and intuitive than a minimum of 0
            // There's also corresponding logic in Link::onBeforeWrite() to also have a minimum of 1
            $sort = $i + 1;
            if ($link->Sort !== $sort && !$link->canEdit()) {
                $this->jsonError(403);
            }
        }
        // Update Sort field on links
        foreach ($newLinkIDs as $i => $linkID) {
            $linkID = $newLinkIDs[$i];
            $link = $linkIDToLink[$linkID];
            $sort = $i + 1;
            if ($link->Sort !== $sort) {
                $link->Sort = $sort;
                $link->write();
            }
        }
        // Send response
        return $this->jsonSuccess(204);
    }

    /**
     * Create the Form used to content manage a Link in a modal
     */
    private function createLinkForm(Link $link, string $operation, bool $excludeLinkTextField = false): Form
    {
        $id = $link->ID;

        // Create the form
        $formFactory = Injector::inst()->get(DefaultFormFactory::class);
        $name = sprintf(LinkFieldController::FORM_NAME_TEMPLATE, $id);
        /** @var Form $form */
        $form = $formFactory->getForm($this, $name, ['Record' => $link]);
        $owner = $this->getOwnerFromRequest();
        $ownerID = $owner->ID;
        $ownerClassName = $owner->ClassName;
        $ownerRelation = $this->getOwnerRelationFromRequest();

        // Remove LinkText if appropriate
        if ($excludeLinkTextField) {
            $form->Fields()->removeByName('LinkText');
        }

        // Add hidden form fields for OwnerID, OwnerClass and OwnerRelation
        if ($operation === 'create') {
            $form->Fields()->push(HiddenField::create('OwnerID')->setValue($ownerID));
            $form->Fields()->push(HiddenField::create('OwnerClass')->setValue($ownerClassName));
            $form->Fields()->push(HiddenField::create('OwnerRelation')->setValue($ownerRelation));
        }
        // Set where the form is submitted to
        $typeKey = LinkTypeService::create()->keyByClassName($link->ClassName);
        $url = $this->Link("linkForm/$id?typeKey=$typeKey&ownerID=$ownerID&ownerClass=$ownerClassName"
            . "&ownerRelation=$ownerRelation");
        $form->setFormAction($url);

        // Add save action button
        $title = $id
            ? _t(__CLASS__ . '.UPDATE_LINK', 'Update link')
            : _t(__CLASS__ . '.CREATE_LINK', 'Create link');
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
            || $operation === 'edit' && !$link->canEdit()
            || $this->getFieldIsReadonlyOrDisabled()
            || $this->getRequest()->getVar('inHistoryViewer')
        ) {
            $form->makeReadonly();
        }

        // Styling
        $form->addExtraClass('form--no-dividers');

        return $form;
    }

    /**
     * Get if the relevant LinkField is readonly or disabled
     */
    private function getFieldIsReadonlyOrDisabled(): bool
    {
        $ownerClass = $this->getOwnerClassFromRequest();
        $ownerRelation = $this->getOwnerRelationFromRequest();

        /** @var LinkField|MultiLinkField $field */
        $field = Injector::inst()->get($ownerClass)->getCMSFields()->dataFieldByName($ownerRelation);
        if (!$field) {
            return false;
        }
        return $field->isReadonly() || $field->isDisabled();
    }

    /**
     * Get a Link object based on the $ItemID request param
     */
    private function linkFromRequest(): Link
    {
        $itemID = $this->itemIDFromRequest();
        if (!$itemID) {
            $this->jsonError(404);
        }
        $link = Link::get()->byID($itemID);
        if (!$link) {
            $this->jsonError(404);
        }
        return $link;
    }

    /**
     * Get all Link objects based on the itemID query string argument
     */
    private function linksFromRequest(): DataList
    {
        $itemIDs = $this->itemIDsFromRequest();
        if (empty($itemIDs)) {
            $this->jsonError(404);
        }
        $links = Link::get()->byIDs($itemIDs);
        if (!$links->exists()) {
            $this->jsonError(404);
        }
        return $links;
    }

    /**
     * Get the $ItemID request param
     */
    private function itemIDFromRequest(): int
    {
        $request = $this->getRequest();
        $itemID = (string) $request->param('ItemID');
        if (!ctype_digit($itemID)) {
            $this->jsonError(404);
        }
        return (int) $itemID;
    }

    /**
     * Get the value of the itemID request query string argument
     */
    private function itemIDsFromRequest(): array
    {
        $request = $this->getRequest();
        $itemIDs = $request->getVar('itemIDs');

        if (!is_array($itemIDs)) {
            $this->jsonError(404);
        }

        $idsAsInt = [];
        foreach ($itemIDs as $id) {
            if (!is_int($id) && !ctype_digit($id)) {
                $this->jsonError(404);
            }
            $idsAsInt[] = (int) $id;
        }

        return $idsAsInt;
    }

    /**
     * Get the ?typeKey request querystring param
     */
    private function typeKeyFromRequest(): string
    {
        $request = $this->getRequest();
        $typeKey = (string) $request->getVar('typeKey');
        if (strlen($typeKey) === 0 || !preg_match('#^[a-z\-]+$#', $typeKey)) {
            $this->jsonError(404);
        }
        return $typeKey;
    }

    /**
     * Get the owner class based on the query string param OwnerClass
     */
    private function getOwnerClassFromRequest(): string
    {
        $request = $this->getRequest();
        $ownerClass = $request->getVar('ownerClass') ?: $request->postVar('OwnerClass');
        if (!is_a($ownerClass, DataObject::class, true)) {
            $this->jsonError(404);
        }

        return $ownerClass;
    }

    /**
     * Get the owner ID based on the query string param OwnerID
     */
    private function getOwnerIDFromRequest(): int
    {
        $request = $this->getRequest();
        $ownerID = (int) ($request->getVar('ownerID') ?: $request->postVar('OwnerID'));
        if ($ownerID === 0) {
            $this->jsonError(404);
        }

        return $ownerID;
    }

    /**
     * Get the owner based on the query string params ownerID, ownerClass, ownerRelation
     * OR the POST vars OwnerID, OwnerClass, OwnerRelation
     */
    private function getOwnerFromRequest(): DataObject
    {
        $ownerID = $this->getOwnerIDFromRequest();
        $ownerClass = $this->getOwnerClassFromRequest();
        $ownerRelation = $this->getOwnerRelationFromRequest();
        /** @var DataObject $obj */
        $obj = Injector::inst()->get($ownerClass);
        $hasOne = $obj->hasOne();
        $hasMany = $obj->hasMany();
        $matchedRelation = false;
        foreach ([$hasOne, $hasMany] as $property) {
            if (!array_key_exists($ownerRelation, $property)) {
                continue;
            }
            $className = $property[$ownerRelation];
            if (is_a($className, Link::class, true)) {
                $matchedRelation = true;
                break;
            }
        }
        if ($matchedRelation) {
            /** @var DataObject $ownerClass */
            $owner = $ownerClass::get()->byID($ownerID);
            if ($owner) {
                return $owner;
            }
        }
        $this->jsonError(404);
    }

    /**
     * Get the owner relation based on the query string param ownerRelation
     * OR the POST var OwnerRelation
     */
    private function getOwnerRelationFromRequest(): string
    {
        $request = $this->getRequest();
        $ownerRelation = $request->getVar('ownerRelation') ?: $request->postVar('OwnerRelation');
        if (!$ownerRelation) {
            $this->jsonError(404);
        }

        return $ownerRelation;
    }
}
