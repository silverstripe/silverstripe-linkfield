# Silverstripe link module

Experimental module looking at how we could implement a link field and a link data object.

# Installation

* Add module report to your list of repositories

```json
"repositories": {
  "silverstripe/linkfield": {
    "type": "git",
    "url": "https://github.com/silverstripe/silverstripe-linkfield.git"
  }
},
```

* Run `composer require silverstripe/linkfield dev-master`

# Sample usage

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
