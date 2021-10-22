# Silverstripe link module

Experimental module looking at how we could implement a link field and a link data object.

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
use SilverStripe\Link\DBLink;
use SilverStripe\Link\Link;
use SilverStripe\Link\LinkField;

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
