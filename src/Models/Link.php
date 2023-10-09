<?php declare(strict_types=1);

namespace SilverStripe\LinkField\Models;

use InvalidArgumentException;
use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CompositeValidator;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\LinkField\JsonData;
use SilverStripe\LinkField\Type\Registry;
use SilverStripe\LinkField\Type\Type;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;
use SilverStripe\LinkField\Extensions\LinkObjectExtension;
use SilverStripe\Versioned\Versioned;
use SilverStripe\LinkField\Models\LinkArea;

/**
 * A Link Data Object. This class should be a subclass, and you should never directly interact with a plain Link
 * instance
 *
 * @property string $Title
 * @property bool $OpenInNew
 */
class Link extends DataObject implements JsonData, Type
{
    private static $table_name = 'LinkField_Link';

    private static array $db = [
        'Title' => 'Varchar',
        'OpenInNew' => 'Boolean',
    ];

    private static array $has_one = [
        'LinkArea' => LinkArea::class
    ];

    private static array $extensions = [
        Versioned::class,
        LinkObjectExtension::class,
    ];

    /**
     * In-memory only property used to change link type
     * This case is relevant for CMS edit form which doesn't use React driven UI
     * This is a workaround as changing the ClassName directly is not fully supported in the GridField admin
     */
    private ?string $linkType = null;

    public function defineLinkTypeRequirements()
    {
        Requirements::add_i18n_javascript('silverstripe/linkfield:client/lang', false, true);
        Requirements::javascript('silverstripe/linkfield:client/dist/js/bundle.js');
        Requirements::css('silverstripe/linkfield:client/dist/styles/bundle.css');
    }

    public function LinkTypeHandlerName(): string
    {
        return 'FormBuilderModal';
    }

    public function generateLinkDescription(array $data): string
    {
        return '';
    }

    public function LinkTypeTile(): string
    {
        return $this->i18n_singular_name();
    }

    public function scaffoldLinkFields(array $data): FieldList
    {
        return $this->getCMSFields();
    }

    /**
     * @return FieldList
     * @throws ReflectionException
     */
    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $linkTypes = $this->getLinkTypes();

            if (static::class === self::class) {
                // Add a link type selection field for generic links
                $fields->addFieldsToTab(
                    'Root.Main',
                    [
                        $linkTypeField = DropdownField::create('LinkType', 'Link Type', $linkTypes),
                    ],
                    'Title'
                );

                $linkTypeField->setEmptyString('-- select type --');
            }
        });

        return parent::getCMSFields();
    }

    /**
     * @return CompositeValidator
     */
    public function getCMSCompositeValidator(): CompositeValidator
    {
        $validator = parent::getCMSCompositeValidator();

        if (static::class === self::class) {
            // Make Link type mandatory for generic links
            $validator->addValidator(RequiredFields::create([
                'LinkType',
            ]));
        }

        return $validator;
    }

    /**
     * Form hook defined in @see Form::saveInto()
     * We use this to work with an in-memory only field
     *
     * @param $value
     */
    public function saveLinkType($value)
    {
        $this->linkType = $value;
    }

    public function onBeforeWrite(): void
    {
        // Detect link type change and update the class accordingly
        if ($this->linkType && DataObject::singleton($this->linkType) instanceof Link) {
            $this->setClassName($this->linkType);
            $this->populateDefaults();
            $this->forceChange();
        }

        parent::onBeforeWrite();
    }

    function setData($data): JsonData
    {
        if (is_string($data)) {
            $data = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException(sprintf(
                    '%s: Decoding json string failred with "%s"',
                    static::class,
                    json_last_error_msg()
                ));
            }
        } elseif ($data instanceof JsonData) {
            $data = $data->jsonSerialize();
        }

        if (!is_array($data)) {
            throw new InvalidArgumentException(sprintf('%s: Could not convert $data to an array.', static::class));
        }

        $typeKey = $data['typeKey'] ?? null;

        if (!$typeKey) {
            throw new InvalidArgumentException(sprintf('%s: $data does not have a typeKey.', static::class));
        }

        $type = Registry::singleton()->byKey($typeKey);

        if (!$type) {
            throw new InvalidArgumentException(sprintf('%s: %s is not a registered Link Type.', static::class, $typeKey));
        }

        $jsonData = $this;

        if ($this->ClassName !== get_class($type)) {
            if ($this->isInDB()) {
                $jsonData = $this->newClassInstance(get_class($type));
            } else {
                $jsonData = Injector::inst()->create(get_class($type));
            }
        }

        foreach ($data as $key => $value) {
            if ($jsonData->hasField($key)) {
                $jsonData->setField($key, $value);
            }
        }

        return $jsonData;
    }

    public function jsonSerialize(): mixed
    {
        $typeKey = Registry::singleton()->keyByClassName(static::class);

        if (!$typeKey) {
            return [];
        }

        $data = $this->toMap();
        $data['typeKey'] = $typeKey;
        // Some of our models (SiteTreeLink in particular) have defined getTitle() methods. We *don't* want to override
        // the 'Title' field (which represent the literal 'Title' Database field) - if we did that, then it would also
        // apply this value into our Edit form. This addition is only use in the LinkField summary
        $data['TitleRelField'] = $this->relField('Title');

        unset($data['ClassName']);
        unset($data['RecordClassName']);

        return $data;
    }

    public function loadLinkData(array $data): JsonData
    {
        $link = new static();

        foreach ($data as $key => $value) {
            if ($link->hasField($key)) {
                $link->setField($key, $value);
            }
        }

        return $link;
    }

    /**
     * Return a rendered version of this form.
     *
     * This is returned when you access a form as $FormObject rather
     * than <% with FormObject %>
     *
     * @return DBHTMLText
     */
    public function forTemplate()
    {
        return $this->renderWith([self::class]);
    }

    /**
     * This method should be overridden by any subclasses
     */
    public function getURL(): string
    {
        return '';
    }

    /**
     * Get all link types except the generic one
     *
     * @throws ReflectionException
     */
    private function getLinkTypes(): array
    {
        $classes = ClassInfo::subclassesFor(self::class);
        $types = [];

        foreach ($classes as $class) {
            if ($class === self::class) {
                continue;
            }

            $types[$class] = ClassInfo::shortName($class);
        }

        return $types;
    }
}
