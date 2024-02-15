---
title: Configuring links and link fields
summary: Advanced usage of the Link model and link fields
---

# Configuring links and link fields

## Controlling what type of links can be created in a LinkField

By default, all [`Link`](api:SilverStripe\LinkField\Models\Link) subclasses can be created by a [`LinkField`](api:SilverStripe\LinkField\Form\LinkField) or [`MultiLinkField`](api:SilverStripe\LinkField\Form\MultiLinkField). This includes any custom `Link` subclasses defined in your project or via a third party module.

If you wish to globally disable one of the default `Link` subclasses for all link field instances, then this can be done using the following YAML configuration with the fully-qualified class name of the relevant default `Link` subclass you wish to disable:

```yml
SilverStripe\LinkField\Models\SiteTreeLink:
  allowed_by_default: false
```

You can also apply this configuration to any of your own custom `Link` subclasses:

```php
namespace App\Model\Link;

use SilverStripe\LinkField\Models\Link;

class MyCustomLink extends Link
{
    private static bool $allowed_by_default = false;
    // ...
}
```

Developers can control the link types allowed for any individual link field. The [`AbstractLinkField::setAllowedTypes()`](api:SilverStripe\LinkField\Form\AbstractLinkField::setAllowedTypes()) method tells the link field which link types that are allowed to be created by it.

> [!TIP]
> Using `AbstractLinkField::setAllowedTypes()` will override the [`allowed_by_default`](api:SilverStripe\LinkField\Models\Link->allowed_by_default) configuration.

```php
namespace App\Model;

use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\LinkField\Models\EmailLink;
use SilverStripe\LinkField\Models\SiteTreeLink;
use SilverStripe\ORM\DataObject;

class MyModel extends DataObject
{
    // ...

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['EmailLinkID', 'LinkList']);
        $fields->addFieldsToTab(
            'Root.Main',
            [
                LinkField::create('EmailLink')->setAllowedTypes([EmailLink::class]),
                MultiLinkField::create('LinkList')->setAllowedTypes([SiteTreeLink::class, EmailLink::class]),
            ],
        );
        return $fields;
    }
}
```

## Excluding the `LinkText` field

Sometimes you might want to have a link which doesn't have text, or for which you handle the text elsewhere. For example you might have a banner with a link, and you only want to use a `Link` record to control where the banner links to.

You can call the [`AbstractLinkField::setExcludeLinkTextField()`](api:SilverStripe\LinkField\Form\AbstractLinkField::setExcludeLinkTextField()) method to remove the `LinkText` field from the link modal for all links connected to that link field.

```php
namespace App\Model;

use SilverStripe\LinkField\Form\LinkField;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\ORM\DataObject;

class MyModel extends DataObject
{
    // ...

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['LinkID', 'LinkList']);
        $fields->addFieldsToTab(
            'Root.Main',
            [
                LinkField::create('Link')->setExcludeLinkTextField(true),
                MultiLinkField::create('LinkList')->setExcludeLinkTextField(true),
            ],
        );
        return $fields;
    }
}
```

## Additional features

You can customise the position of the link type in the menu by setting the the [`menu_priority`](api:SilverStripe\LinkField\Models\Link->menu_priority) configuration property. The priority is in ascending order (i.e. a link with a lower priority value will be displayed higher in the list).

You can also set an icon that will correspond to a specific type of link by setting the value of the [`icon`](api:SilverStripe\LinkField\Models\Link->icon) configuration property. The value of this configuration corresponds to the css class of the icon to be used.

> [!TIP]
> You can find the set of available icons in the [pattern library](https://silverstripe.github.io/silverstripe-pattern-lib/?path=/story/admin-icons--icon-reference).
>
> Make sure you prefix the icon names with `font-icon-`.

```yml
SilverStripe\LinkField\Models\PhoneLink:
  menu_priority: 1
  icon: 'font-icon-tablet'
```

You can also define these values for a new link type.

```php
namespace App\Model\Link;

use SilverStripe\LinkField\Models\Link;

class MyCustomLink extends Link
{
    private static int $menu_priority = 1;

    private static $icon = 'font-icon-address-card';
    // ...
}
```
