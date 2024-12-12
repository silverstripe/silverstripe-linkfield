---
title: Basic usage
summary: How to set up your link relations and fields
---

# Basic usage

The [`Link`](api:SilverStripe\LinkField\Models\Link) model can be used with a `has_one` or `has_many` relation, depending on whether you want one or multiple links.

> [!WARNING]
> Using `many_many` relations with the `Link` model is not supported. The `can*` permission methods on `Link` rely on having a single owner record they can inherit permissions from.
>
> See [model-level permissions](https://docs.silverstripe.org/en/developer_guides/model/permissions) for more information about these methods.

The [`LinkField`](api:SilverStripe\LinkField\Form\LinkField) form field is used to manage links in a `has_one` relation, and [`MultiLinkField`](api:SilverStripe\LinkField\Form\MultiLinkField) is for links in a `has_many` relation. If you are relying on auto-scaffolded fields, these will be provided for you.

```php
namespace App\Model;

use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

class MyModel extends DataObject
{
    private static array $has_one = [
        'HasOneLink' => Link::class,
    ];

    private static $has_many = [
        'HasManyLinks' => Link::class . '.Owner',
    ];

    private static array $owns = [
        'HasOneLink',
        'HasManyLinks',
    ];

    private static array $cascade_deletes = [
        'HasOneLink',
        'HasManyLinks',
    ];

    private static array $cascade_duplicates = [
        'HasOneLink',
        'HasManyLinks',
    ];
}
```

> [!CAUTION]
> Adding the relationship(s) to the `$owns` configuration property is required for versioning (publishing) to work correctly. See [cascade publishing](https://docs.silverstripe.org/en/developer_guides/model/versioning/#cascade-publishing) to learn more.

The `$cascade_deletes` and `$cascade_duplicates` configuration is optional but beneficial. Without applying `$cascade_deletes` for example, deleting the parent record (a `MyModel` record in the example above) will result in orphaned links which are still in your database but not owned by anything. See [cascading deletions](https://docs.silverstripe.org/en/developer_guides/model/relations/#cascading-deletions) and [cascading duplications](https://docs.silverstripe.org/en/developer_guides/model/relations/#cascading-duplications) for more information.

## Multiple `has_many` relations on the same model

If you have multiple `has_many` relations on the same class, they should all point at the "Owner" `has_one` relation on `Link` using [dot notation](https://docs.silverstripe.org/en/developer_guides/model/relations/#dot-notation). This is important for the permission model to work correctly.

```php
namespace App\Model;

use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

class MyModel extends DataObject
{
    private static $has_many = [
        'HasManyLinksOne' => Link::class . '.Owner',
        'HasManyLinksTwo' => Link::class . '.Owner',
    ];
    // ...
}
```

## Form fields

When relying on auto-scaffolded fields for your relations, an appropriate `LinkField` or `MultiLinkField` instance will be scaffolded for you. But in some cases you may want to explicitly set those fields up yourself, so here's an example of how to do that.

```php
namespace App\Model;

use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

class MyModel extends DataObject
{
    private static array $has_one = [
        'HasOneLink' => Link::class,
    ];

    private static $has_many = [
        'HasManyLinks' => Link::class . '.Owner',
    ];
    // ...

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab(
            'Root.Main',
            [
                LinkField::create('HasOneLink'),
                MultiLinkField::create('HasManyLinks'),
            ]
        );

        return $fields;
    }
}
```

## Validation

Custom links can have validation set using standard [model validation](https://docs.silverstripe.org/en/developer_guides/forms/validation/#model-validation). This is true both for the validation of the link data itself, as well as validating relations to the `Link` class.

For example you can make sure you have a link in your `has_one` or `has_many` relation using a [`RequiredFieldsValidator`](api:SilverStripe\Forms\Validation\RequiredFieldsValidator) validator:

```php
namespace App\Model;

use SilverStripe\Forms\Validation\CompositeValidator;
use SilverStripe\Forms\Validation\RequiredFieldsValidator;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;

class MyModel extends DataObject
{
    private static array $has_one = [
        'HasOneLink' => Link::class,
    ];

    private static $has_many = [
        'HasManyLinks' => Link::class . '.Owner',
    ];
    // ...

    public function getCMSCompositeValidator(): CompositeValidator
    {
        $validator = parent::getCMSCompositeValidator();
        $validator->addValidator(RequiredFieldsValidator::create(['HasOneLink', 'HasManyLinks']));
        return $validator;
    }
}
```

> [!TIP]
> You can also update validation logic of a given `Link` class by creating an `Extension` and implementing the `updateCMSCompositeValidator()` method.

## Unversioned links

The `Link` model has the [`Versioned`](api:SilverStripe\Versioned\Versioned) extension applied to it by default. If you wish for links to not be versioned, then remove the extension from the `Link` model in the project's `app/_config.php` file.

```php
// app/_config.php

use SilverStripe\LinkField\Models\Link;
use SilverStripe\Versioned\Versioned;

Link::remove_extension(Versioned::class);
```

If you do this, you don't need to apply the `$owns` configuration described in [basic usage](#basic-usage) above.

See [versioning](https://docs.silverstripe.org/en/developer_guides/model/versioning/) for more information about the implications of making this change.
