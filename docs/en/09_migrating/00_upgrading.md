---
title: Upgrading from older versions
summary: A guide for migrating from older versions of silverstripe/linkfield
---

# Upgrading from older versions

> [!NOTE]
> If your site is running Silverstripe CMS 4.x, update your constraint for `silverstripe/linkfield` to the latest available version of linkfield v3 and upgrade to CMS 5 first.
> There should be no additional steps required for upgrading from linkfield 2.x to linkfield 3.x.
> Once you have finished upgrading to CMS 5, return to this guide and continue the linkfield upgrade.

There are three major changes introduced in `silverstripe/linkfield` 4.0.0:

1. The `Title` database field has been renamed to `LinkText`
1. There is now a `has_one` relation on the [`Link`](api:SilverStripe\LinkField\Models\Link) class called `Owner` which must contain the record that owns the link.
1. There is now a form field for managing `has_many` relations to link

This guide will help you update to the latest version of `silverstripe/linkfield` and run a task that will automatically update your data.

> [!WARNING]
> This guide does not provide an upgrade path for links which were stored using the legacy `DBLink` database field type.
> If you have any links like that in your project, you will need to write your own migration script.

## Setup

> [!TIP]
> We strongly recommend taking a backup of your database before doing anything else.
> This will ensure you have a known state to revert to in case anything goes wrong.

### Resolve deprecation warnings

Enable [deprecation warnings](https://docs.silverstripe.org/en/upgrading/deprecations/) and resolve any deprecation warnings that are related to `silverstripe/linkfield`. When you have resolved all deprecation warnings, you can disable the deprecation warnings.

### Update your dependencies

Update your composer dependency for `silverstripe/linkfield` to `^4`

```bash
composer require silverstripe/linkfield:^4
```

### Configure the migration task

1. Enable the task:

    ```yml
    SilverStripe\LinkField\Tasks\LinkFieldMigrationTask:
        is_enabled: true
    ```

1. Declare any `has_many` relations that need to be migrated:

    ```yml
    SilverStripe\LinkField\Tasks\LinkFieldMigrationTask:
        # ...
        has_many_links_data:
        # The class where the has_many relation is declared
        App\Model\MyClass:
            # The name of the has_many relation
            LinkListOne:
            # The class where the old has_one relation was declared
            # This will be either be Link or a Link subclass (not an extension applied to a Link class)
            linkClass: 'SilverStripe\LinkField\Models\Link'
            # The old name of the has_one relation on Link or a Link subclass
            hasOne: 'MyOwner'
    ```

1. Declare any classes that may have `has_one` relations to `Link`, but which do not *own* the link. Classes declared here will include any subclasses.
    For example if a custom link has a `has_many` relation to some class which does not own the link, declare that class here so it is not incorrectly identified as the owner of the link:

    ```yml
    SilverStripe\LinkField\Tasks\LinkFieldMigrationTask:
        # ...
        classes_that_are_not_link_owners:
        - App\Model\SomeClass
    ```

### Update your codebase

1. If the `has_one` relation for the record which owns the links (e.g. `Page`) has a corresponding `belongs_to` relation on the `Link` model (either added via an extension or YAML configuration), remove the `belongs_to` relation from the `Link` model.
1. For any `has_many` relations to links on the record that owns the links (e.g. `Page`), update the dot notation to point to the `Owner` relation:

    ```diff
    private static array $has_many = [
    -   'LinkListOne' => Link::class . '.FirstHasOne',
    +   'LinkListOne' => Link::class . '.Owner',
    -   'LinkListTwo' => Link::class . '.SecondHasOne',
    +   'LinkListTwo' => Link::class . '.Owner',
    ];
    ```

1. Remove the `has_one` relation on the relevant `Link` class which was storing the `has_many` relations.
    - e.g. for the above, remove the `FirstHasOne` and `SecondHasOne` relations from the `Link` class. You may have applied these via an `Extension` class or via YAML configuration.
1. If the models that have `has_one` or `has_many` relations to link don't already use `$owns` configuration for those relations, add that now. You may also want to set `$cascade_deletes` and `$cascade_duplicates` configuration. See [basic usage](../01_basic_usage.md) for more details.

> [!WARNING]
> `many_many` relations to `Link` are not supported. If you have any `many_many` relations to links you will need to migrate these to `has_many` relations yourself.

## Customising the migration

There are many extension hooks in the [`LinkFieldMigrationTask`](api:SilverStripe\LinkField\Tasks\LinkFieldMigrationTask) class which you can use to change its behaviour or add additional migration steps. We strongly recommend taking a look at the source code to see if your use case requires any customisations.

Some scenarios where you may need customisations include:

- You had applied the [`Versioned`](api:SilverStripe\Versioned\Versioned) extension to `Link` and want to prevent publishing links that should remain in draft.
- You have a setup that allows moving links from one page to another, and want to ensure the correct `Owner` is set for old versions of the link record.
- You have additional custom columns that need to be migrated, which the task doesn't know about.

## Migrating

For databases that support transactions, the full data migration is performed within a single transaction, and any errors in the migration will result in rolling back all changes. This means you can address whatever caused the error and then run the task again.

> [!NOTE]
> We strongly recommend running this task in a local development environment before trying it in production.
> There may be edge cases that the migration task doesn't account for which need to be resolved.

1. Run dev/build and flush your cache (use the method you will be using the for next step - i.e. if you're running the task via the terminal, make sure to flush via the terminal)
    - via the browser: `https://www.example.com/dev/build?flush=1`
    - via a terminal: `sake dev/build flush=1`
1. Run the task
    - via the browser: `https://www.example.com/dev/tasks/linkfield-tov4-migration-task`
    - via a terminal: `sake dev/tasks/linkfield-tov4-migration-task`
1. If you have any `has_many` relations to `Link`, replace the `GridField` you're currently using the manage those links with a [`MultiLinkField`](api:SilverStripe\LinkField\Form\MultiLinkField). See [basic usage](../01_basic_usage.md) for details.

The task performs the following steps:

1. Migrates content in the `Title` column into the new `LinkText` column and removes the `Title` column from the database.
1. Migrates any `has_many` relations to link which were declared in [`LinkFieldMigrationTask.has_many_links_data`](api:SilverStripe\LinkField\Tasks\LinkFieldMigrationTask->has_many_links_data) and removes the old `*ID` (and `*Class` for polymorphic relations) columns from the old `has_one` relations.
1. Set the `Owner` relation for `has_one` relations to links.
1. Publishes all links, unless you have removed the `Versioned` extension.
1. Output a table with any links which are lacking the data required to generate a URL.
    - You can skip this step by adding `?skipBrokenLinks=1` to the end of the URL: `https://www.example.com/dev/tasks/linkfield-tov4-migration-task?skipBrokenLinks=1`.
    - If you're running the task in a terminal, you can add `skipBrokenLinks=1` as an argument: `sake dev/tasks/linkfield-tov4-migration-task skipBrokenLinks=1`.
