<?php

namespace SilverStripe\LinkField\Extensions;

use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\LinkField\Form\FormFactory;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\Dev\Deprecation;

/**
 * Enhance the insert / edit link button label to match the model data state
 *
 * @method FormFactory getOwner()
 * @deprecated 3.0.0 Will be removed without equivalent functionality to replace it
 */
class FormFactoryExtension extends Extension
{
    public function __construct()
    {
        Deprecation::withNoReplacement(function () {
            Deprecation::notice('3.0.0', 'Will be removed without equivalent functionality to replace it', Deprecation::SCOPE_CLASS);
        });
        parent::__construct();
    }

    /**
     * Extension point in @see FormFactory::getFormActions()
     *
     * @param FieldList $actions
     * @param RequestHandler $controller
     * @param string $name
     * @param array $context
     * @return void
     */
    public function updateFormActions(FieldList $actions, RequestHandler $controller, string $name, array $context): void
    {
        if (!array_key_exists('LinkType', $context)) {
            // We couldn't find any link model
            return;
        }

        /** @var Link $linkType */
        $linkType = $context['LinkType'];

        if (!$linkType->exists()) {
            // This is a new link, so we don't need to to any further customisation
            return;
        }

        /** @var FormAction $insertAction */
        $insertAction = $actions->fieldByName('action_insert');

        if (!$insertAction) {
            // We couldn't find the insert action
            return;
        }

        // Update the title of the action to reflect the link model data state
        $insertActionTitle = _t('Admin.EDIT_LINK', 'Edit link');
        $insertAction->setTitle($insertActionTitle);
    }
}
