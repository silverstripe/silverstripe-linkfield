<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormField;
use SilverStripe\LinkField\Services\LinkTypeService;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Forms\Tip;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Form\MultiLinkField;

/**
 * A Link DataObject. This class should be treated as abstract. You should never directly interact with a plain Link
 * instance
 *
 * Note that links should be added via a has_one or has_many relation, NEVER a many_many relation. This is because
 * some functionality such as the can* methods rely on having a single Owner.
 *
 * @property string $Title
 * @property bool $OpenInNew
 */
class Link extends DataObject
{
    private static $table_name = 'LinkField_Link';

    private static array $db = [
        'LinkText' => 'Varchar',
        'OpenInNew' => 'Boolean',
        'Sort' => 'Int',
    ];

    private static array $has_one = [
        // Note that this handles one-to-many relations AND one-to-one relations.
        // Any has_one pointing at Link will be intentionally double handled - this allows us to use the owner
        // for permission checks and to link back to the owner from reports, etc.
        // See also the Owner method.
        'Owner' => [
            'class' => DataObject::class,
            DataObjectSchema::HAS_ONE_MULTI_RELATIONAL => true,
        ],
    ];

    private static $default_sort = 'Sort';

    private static array $extensions = [
        Versioned::class,
    ];

    /**
     * Set the priority of this link type in the link picker.
     * A link with a higher priority value will be displayed lower in the list.
     */
    private static int $menu_priority = 100;

    /**
     * Whether this link type is allowed by default
     * If this is set to `false` then this type of Link can still be manually allowed
     * on a per field basis with AbstractLinkField::setAllowedTypes();
     */
    private static bool $allowed_by_default = true;

    /**
     * The css class for the icon to display for this link type
     */
    private static $icon = 'font-icon-link';

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $linkTextField = $fields->dataFieldByName('LinkText');
            $linkTextField->setTitle(_t(__CLASS__ . '.LINK_TEXT_TITLE', 'Link text'));
            $linkTextField->setTitleTip(new Tip(_t(
                Link::class . '.LINK_TEXT_TEXT_DESCRIPTION',
                'If left blank, an appropriate default will be used on the front-end',
            )));

            $fields->removeByName('Sort');

            $openInNewField = $fields->dataFieldByName('OpenInNew');
            $openInNewField->setTitle(_t(__CLASS__ . '.OPEN_IN_NEW_TITLE', 'Open in new window?'));
        });
        $this->afterUpdateCMSFields(function (FieldList $fields) {
            // Move the LinkText and OpenInNew fields to the bottom of the form if it hasn't been removed in
            // a subclasses getCMSFields() method
            foreach (['LinkText', 'OpenInNew'] as $name) {
                $field = $fields->dataFieldByName($name);
                if ($field) {
                    $fields->removeByName($name);
                    $fields->addFieldToTab('Root.Main', $field);
                }
            }
        });

        return parent::getCMSFields();
    }

    /**
     * Get a short description of the link. This is displayed in LinkField as an indication of what the link is pointing at.
     *
     * This method should be overridden by any subclasses
     */
    public function getDescription(): string
    {
        return '';
    }

    /**
     * Get the URL this Link links to.
     *
     * This method should be overridden by any subclasses
     */
    public function getURL(): string
    {
        return '';
    }

    /**
     * Get the react component used to render the modal form.
     */
    public function getLinkTypeHandlerName(): string
    {
        return 'FormBuilderModal';
    }

    /**
     * The title that will be displayed in the dropdown
     * for selecting the link type to create.
     *
     * Subclasses should override this.
     * It will use the singular_name by default.
     */
    public function getMenuTitle(): string
    {
        return $this->i18n_singular_name();
    }

    public function getTitle(): string
    {
        // If we have link text, we can just bail out without any changes
        if ($this->LinkText) {
            return $this->LinkText;
        }

        $defaultLinkTitle = $this->getDefaultTitle();

        $this->extend('updateDefaultLinkTitle', $defaultLinkTitle);

        return $defaultLinkTitle;
    }

    /**
     * This method process the defined singular_name of Link class
     * to get the short code of the Link class name.
     *
     * Or If the name is not defined (by redefining $singular_name in the subclass),
     * this use the class name. The Link prefix is removed from the class name
     * and the resulting name is converted to lowercase.
     * Example: Link => link, EmailLink => email, FileLink => file, SiteTreeLink => sitetree
     */
    public function getShortCode(): string
    {
        return strtolower(rtrim(ClassInfo::shortName($this), 'Link')) ?? '';
    }

    public function scaffoldFormFieldForHasOne(
        string $fieldName,
        ?string $fieldTitle,
        string $relationName,
        DataObject $ownerRecord
    ): FormField {
        return LinkField::create($relationName, $fieldTitle);
    }

    public function scaffoldFormFieldForHasMany(
        string $relationName,
        ?string $fieldTitle,
        DataObject $ownerRecord,
        bool &$includeInOwnTab
    ): FormField {
        $includeInOwnTab = false;
        return MultiLinkField::create($relationName, $fieldTitle);
    }

    /**
     * Get a string representing the versioned state of the link.
     */
    public function getVersionedState(): string
    {
        if (!$this->exists()) {
            return 'unsaved';
        }
        if ($this->hasExtension(Versioned::class)) {
            if ($this->isPublished()) {
                if ($this->isModifiedOnDraft()) {
                    return 'modified';
                }
                return 'published';
            }
            return 'draft';
        }
        // Unversioned - links are saved in the modal so there is no 'dirty state' and
        // when undversioned saved is the same thing as published
        return 'unversioned';
    }

    protected function onBeforeWrite(): void
    {
        // Ensure a Sort value is set and that it's one larger than any other Sort value for
        // this owner relation so that newly created Links on MultiLinkField's are properly sorted
        if (!$this->Sort) {
            $this->Sort = Link::get()->filter([
                'OwnerID' => $this->OwnerID,
                'OwnerRelation' => $this->OwnerRelation,
            ])->max('Sort') + 1;
        }

        parent::onBeforeWrite();
    }

    /**
     * Get data for this link type which is required for displaying this link in the react component.
     */
    public function getData(): array
    {
        $typeKey = LinkTypeService::create()->keyByClassName(static::class);

        if (!$typeKey) {
            return [];
        }

        return [
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'canDelete' => $this->canDelete(),
            'versionState' => $this->getVersionedState(),
            'statusFlags' => $this->getStatusFlags(),
            'typeKey' => $typeKey,
            'sort' => $this->Sort,
        ];
    }

    /**
     * Return a rendered version of this link.
     *
     * This is returned when you access a link as $LinkRelation or $Me rather
     * than <% with LinkRelation %>
     */
    public function forTemplate(): string
    {
        // First look for a subclass of the email template e.g. EmailLink.ss which may be defined
        // in a project. Fallback to using the generic Link.ss template which this module provides
        return (string) $this->renderWith([static::class, Link::class]);
    }

    /**
     * Get the owner of this link, if there is one.
     *
     * Returns null if the reciprocal relation is a has_one which no longer contains this link
     * or if there simply is no actual owner record in the db.
     */
    public function Owner(): ?DataObject
    {
        $owner = $this->getComponent('Owner');

        // Since the has_one is being stored in two places, double check the owner
        // actually still owns this record. If not, return null.
        if ($this->OwnerRelation && $owner->getRelationType($this->OwnerRelation) === 'has_one') {
            $idField = "{$this->OwnerRelation}ID";
            if ($owner->$idField !== $this->ID) {
                return null;
            }
        }

        // Return null if there simply is no owner
        if (!$owner || !$owner->isInDB()) {
            return null;
        }

        return $owner;
    }

    public function canView($member = null)
    {
        return $this->canPerformAction(__FUNCTION__, $member);
    }

    public function canEdit($member = null)
    {
        return $this->canPerformAction(__FUNCTION__, $member);
    }

    public function canDelete($member = null)
    {
        return $this->canPerformAction(__FUNCTION__, $member);
    }

    public function canCreate($member = null, $context = [])
    {
        // Allow extensions to override permission checks
        $results = $this->extendedCan(__FUNCTION__, $member, $context);
        if (isset($results)) {
            return $results;
        }

        // Assume anyone can create a link by default - there's no way to determine
        // what the "owner" record is going to be ahead of time, but if the user
        // can't edit the owner then the linkfield will be read-only anyway, so we
        // can rely on that to determine who can create links.
        return true;
    }

    public function can($perm, $member = null, $context = [])
    {
        $check = ucfirst(strtolower($perm));
        return match ($check) {
            'View', 'Create', 'Edit', 'Delete' => $this->{"can$check"}($member, $context),
            default => parent::can($perm, $member, $context)
        };
    }

    /**
     * Get the title that will be displayed if there is no LinkText.
     */
    protected function getDefaultTitle(): string
    {
        $default = $this->getDescription() ?: $this->getURL();
        if (!$default) {
            $default = _t(static::class . '.MISSING_DEFAULT_TITLE', '(No value provided)');
        }
        return $default;
    }

    private function canPerformAction(string $canMethod, $member, $context = [])
    {
        // Allow extensions to override permission checks
        $results = $this->extendedCan($canMethod, $member, $context);
        if (isset($results)) {
            return $results;
        }

        // If we have an owner, rely on it to tell us what we can and can't do
        $owner = $this->Owner();
        if ($owner && $owner->exists()) {
            // Can delete or create links if you can edit its owner.
            if ($canMethod === 'canCreate' || $canMethod === 'canDelete') {
                $canMethod = 'canEdit';
            }
            return $owner->$canMethod($member, $context);
        }

        // Default to DataObject's permission checks
        return parent::$canMethod($member, $context);
    }
}
