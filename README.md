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
        'HasManyLinks' => Link::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Don't forget to remove the auto-scaffolded fields!
        $fields->removeByName(['HasOneLinkID', 'Links']);

        $fields->addFieldsToTab(
            'Root.Main',
            [
                LinkField::create('HasOneLink'),
                MultiLinkField::create('HasManyLinks'),
            ],
        );

        return $fields;
    }
}
```

Note that you also need to add a `has_one` relation on the `Link` model to match your `has_many` here. See [official docs about `has_many`](https://docs.silverstripe.org/en/developer_guides/model/relations/#has-many)

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

## Migrating from Shae Dawson's Linkable module

https://github.com/sheadawson/silverstripe-linkable

Shae Dawson's Linkable module was a much loved, and much used module. It is, unfortunately, no longer maintained. We
have provided some steps and tasks that we hope can be used to migrate your project from Linkable to LinkField.

* [Migration docs](docs/en/linkable-migration.md)
