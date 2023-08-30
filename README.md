# Silverstripe link module

[![Build Status](https://github.com/silverstripe/silverstripe-linkfield/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/silverstripe-linkfield/actions/workflows/ci.yml)
[![Latest Stable Version](http://poser.pugx.org/silverstripe/linkfield/v)](https://packagist.org/packages/silverstripe/linkfield)
[![Total Downloads](http://poser.pugx.org/silverstripe/linkfield/downloads)](https://packagist.org/packages/silverstripe/linkfield)
[![Latest Unstable Version](http://poser.pugx.org/silverstripe/linkfield/v/unstable)](https://packagist.org/packages/silverstripe/linkfield)
[![License](http://poser.pugx.org/silverstripe/linkfield/license)](https://packagist.org/packages/silverstripe/linkfield)
[![PHP Version Require](http://poser.pugx.org/silverstripe/linkfield/require/php)](https://packagist.org/packages/silverstripe/linkfield)

Link management module for Silverstripe CMS.

## Installation

Installation via composer.

### Stable version (GraphQL v3)

`composer require silverstripe/linkfield 1.x-dev`

### Experimental version (GraphQL v4)

`composer require silverstripe/linkfield 2.x-dev`

### Known issues

You may need to add the repository URL into your `composer.json` via the `repositories` field (example below).

```json
"repositories": {
  "silverstripe/linkfield": {
    "type": "git",
    "url": "https://github.com/silverstripe/silverstripe-linkfield.git"
  }
},
```

## Sample usage

```php
<?php
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\LinkField\DBLink;
use SilverStripe\LinkField\Link;
use SilverStripe\LinkField\LinkField;

class Page extends SiteTree
{
    private static $db = [
        'DbLink' => DBLink::class
    ];

    private static $has_one = [
        'HasOneLink' => Link::class,
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->insertBefore('Title', LinkField::create('HasOneLink'));
        $fields->insertBefore('Title', LinkField::create('DbLink'));

        return $fields;
    }
}
```

## Migrating from Shae Dawson's Linkable module

https://github.com/sheadawson/silverstripe-linkable

Shae Dawson's Linkable module was a much loved, and much used module. It is, unfortunately, no longer maintained. We
have provided some steps and tasks that we hope can be used to migrate your project from Linkable to LinkField.

* [Migraiton docs](docs/en/linkable-migration.md)
