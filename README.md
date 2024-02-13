# Silverstripe link module

[![Build Status](https://github.com/silverstripe/silverstripe-linkfield/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/silverstripe-linkfield/actions/workflows/ci.yml)
[![Latest Stable Version](http://poser.pugx.org/silverstripe/linkfield/v)](https://packagist.org/packages/silverstripe/linkfield)
[![Total Downloads](http://poser.pugx.org/silverstripe/linkfield/downloads)](https://packagist.org/packages/silverstripe/linkfield)
[![Latest Unstable Version](http://poser.pugx.org/silverstripe/linkfield/v/unstable)](https://packagist.org/packages/silverstripe/linkfield)
[![License](http://poser.pugx.org/silverstripe/linkfield/license)](https://packagist.org/packages/silverstripe/linkfield)
[![PHP Version Require](http://poser.pugx.org/silverstripe/linkfield/require/php)](https://packagist.org/packages/silverstripe/linkfield)

This module provides a Link model and CMS interface for managing different types of links. Including:

* Emails
* External links
* Links to pages within the CMS
* Links to assets within the CMS
* Phone numbers

## Installation

Installation via composer.

```sh
composer require silverstripe/linkfield
```

## Sample usage

```php
<?php
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\LinkField\ORM\DBLink;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Form\MultiLinkField;

class Page extends SiteTree
{
    private static array $has_one = [
        'HasOneLink' => Link::class,
    ];

    private static $has_many = [
        // Multiple has_many relations on the same class should point at the same has_one on Link.
        'HasManyLinksOne' => Link::class . '.Owner',
        'HasManyLinksTwo' => Link::class . '.Owner',
    ];

    private static array $owns = [
        'HasOneLink',
        'HasManyLinksOne',
        'HasManyLinksTwo',
    ];

    private static array $cascade_deletes = [
        'HasOneLink',
        'HasManyLinksOne',
        'HasManyLinksTwo',
    ];

    private static array $cascade_duplicates = [
        'HasOneLink',
        'HasManyLinksOne',
        'HasManyLinksTwo',
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Don't forget to remove the auto-scaffolded fields!
        $fields->removeByName(['HasOneLinkID', 'HasManyLinksOne', 'HasManyLinksTwo']);

        $fields->addFieldsToTab(
            'Root.Main',
            [
                LinkField::create('HasOneLink'),
                MultiLinkField::create('HasManyLinksOne'),
                MultiLinkField::create('HasManyLinksTwo'),
            ],
        );

        return $fields;
    }
}
```

Adding the relationship(s) to the `$owns`, `$cascade_deletes`, and `$cascade_duplicates` config properties is required for versioning (publishing) to work correctly.

## Default title for each link type

By default, if the title for the link has not been set, then the default title will be used instead according to the type of link that is used. Default link is not stored in the database as link title. This value is used only when rendering page content.

The default title value can be updated using an `Extension` with an `updateDefaultLinkTitle()` method and applying that extension to a subclass of `Link`.

```php
// app/src/ExternalLinkExtension.php

namespace App\Extensions;

use SilverStripe\Core\Extension;

class ExternalLinkExtension extends Extension
{
    public function updateDefaultLinkTitle(&$defaultLinkTitle): void
    {
        $defaultLinkTitle = sprintf('External link: %s', $this->owner->ExternalUrl);
    }
}
```

## Controlling what type of links can be created in a LinkField

By default, all `Link` subclasses can be created by a LinkField. This includes any custom `Link` subclasses defined in your projects or via third party module.
Developers can control the link types allowed for individual `LinkField`. The `setAllowedTypes` method only allow link types that have been provided as parameters.

```php
$fields->addFieldsToTab(
    'Root.Main',
    [
        MultiLinkField::create('PageLinkList')
            ->setAllowedTypes([ SiteTreeLink::class ]),
        Link::create('EmailLink')
            ->setAllowedTypes([ EmailLink::class ]),
    ],
);
```

## Excluding the `LinkText` field

Sometimes you might want to have a link which doesn't have text, or for which you handle the text elsewhere. For example you might have a banner with a link, and you only want to use `LinkField` to control where the banner links to.

You can call the `setExcludeLinkTextField()` method to remove the `LinkText` field from the link modal for all links connected to that link field.

```php
$fields->addFieldsToTab(
    'Root.Main',
    [
        MultiLinkField::create('LinkList')
            ->setExcludeLinkTextField(true),
        Link::create('Link')
            ->setExcludeLinkTextField(true),
    ],
);
```

## Unversioned links

The `Link` model has the `Versioned` extension applied to it by default. If you wish for links to not be versioned, then remove the extension from the `Link` model in the projects `app/_config.php` file.

```php
// app/_config.php

use SilverStripe\LinkField\Models\Link;
use SilverStripe\Versioned\Versioned;

Link::remove_extension(Versioned::class);
```

## Additional features

The developer can customise the position of the link type in the menu by setting the `$menu_priority` value. The priority is in ascending order (i.e. a link with a higher priority value will be displayed lower in the list).

The developer can also set an icon that will correspond to a specific type of link by setting the value of the `$icon` configuration property. The value of this configuration corresponds to the css class of the icon to be used.

```yml
SilverStripe\LinkField\Models\PhoneLink:
  icon: 'font-icon-menu-help'
  menu_priority: 1
```

The developer can also define these values for a new link type.

```php
<?php

use SilverStripe\LinkField\Models\Link;

class MyCustomLink extends Link
{
    private static int $menu_priority = 1;
    private static $icon = 'font-icon-custom';
}
```

## Custom link validation

Custom links can have validation set using standard [model validation](https://docs.silverstripe.org/en/5/developer_guides/forms/validation/#model-validation).

## Automatic publishing of linked pages and files

When publishing a link that is either a "Page on this site" or a "Link to a file", the page or file that is linked to will not be automatically published when the link is published. This is to prevent unintentional publishing of draft or modified pages and files. However this does mean that content authors may publish links to unpublished pages or files which will return a 404 when a regular user attempts to use the link.

The link is itself published when the link's "owner" is published. The links owner is a page or another `DataObject` that the link has been added to as a relation.

If you wish to have pages and files that are linked to be automatically published when the link is published, then add the following YAML configuration in your project:

```yml
# "Page on this site"
SilverStripe\LinkField\Models\SiteTreeLink:
  owns:
    - Page

# "Link to a file"
SilverStripe\LinkField\Models\FileLink:
  owns:
    - File
```

Read more about [defining ownership between related objects](https://docs.silverstripe.org/en/5/developer_guides/model/versioning/#defining-ownership-between-related-versioned-dataobject-models).

## Migration from LinkField v3 to v4

The `Title` DB field has been renamed to `LinkText`

You can manually rename this column in your database with the following code:

```php
// app/_config.php
use SilverStripe\LinkField\Models\Link;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;

// Only run this once
// This will rename the `Title` database column to `LinkText` in all relevant tables
$linkTable = DataObject::getSchema()->baseDataTable(Link::class);
DB::get_conn()->getSchemaManager()->renameField($linkTable, 'Title', 'LinkText');
```

It's recommended to put this code in a `BuildTask` so that you can run it exactly once, and then remove that code in a future deployment.

## Migrating from Shae Dawson's Linkable module

https://github.com/sheadawson/silverstripe-linkable

Shae Dawson's Linkable module was a much loved, and much used module. It is, unfortunately, no longer maintained. We
have provided some steps and tasks that we hope can be used to migrate your project from Linkable to LinkField.

* [Migration docs](docs/en/linkable-migration.md)
