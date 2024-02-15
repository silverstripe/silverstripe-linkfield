---
title: Upgrading from older versions
summary: A guide for migrating from older versions of silverstripe/linkfield
---

# Upgrading from older versions

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
