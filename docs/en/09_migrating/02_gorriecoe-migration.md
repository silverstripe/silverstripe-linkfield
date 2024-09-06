---
title: Migrating from gorriecoe/silverstripe-linkfield
summary: A guide for migrating from gorriecoe/silverstripe-linkfield
---

# Migrating from `gorriecoe/silverstripe-linkfield`

There are a few major changes between `gorriecoe/silverstripe-linkfield` and `silverstripe/linkfield`:

1. Link types are defined via subclasses in `silverstripe/linkfield` as opposed to configuration within a single model.
1. `silverstripe/linkfield` doesn't support `many_many` relations - these will be migrated to `has_many` relations instead.
1. Many fields and relations have different names.
1. The default title for a link isn't stored in the database - if the `LinkText` field is left blank, nothing gets stored in the database for that field.
    - This means any links migrated which had the default title set will be migrated with that title as explicit `LinkText`, which will not update automatically when you change the link URL.
    - If you want the `LinkText` for those links to update automatically, you will need to either [customise the migration](#customising-the-migration) or manually unset the `LinkText` for those links afterward.

The following additional items were identified as feature gaps, which may require some additional work to implement if you require them:

- The phone number for `PhoneLink` isn't validated, except to ensure there is a value present.
- `PhoneLink` doesn't have template helper methods for its `Phone` database field.
- The `ExternalLink` type doesn't allow relative URLs.
  - Any existing relative URLs will be migrated with their relative paths intact, but editing them will require updating them to be absolute URLs.
- There are no `addExtraClass()` or related methods for templates. If the default templates and CSS classnames don't suit your requirements you will need to override them.
- There are no `SiteTree` helpers like `isCurrent()`, `isOrphaned()` etc. You can call those methods on the `Page` relation in `SiteTreeLink` instead.
- There is no `link_to_folders` configuration - `FileLink` uses `UploadField` instead which doesn't allow linking to folders.
- There are no GraphQL helper methods or pre-existing GraphQL schema - just use regular GraphQL scaffolding if you need to fetch the links via GraphQL.
- You can't change the type of a link after creating it.
- The [`DefineableMarkupID`](https://github.com/elliot-sawyer/silverstripe-link/blob/master/src/extensions/DefineableMarkupID.php) and [`DBStringLink`](https://github.com/elliot-sawyer/silverstripe-link/blob/master/src/extensions/DBStringLink.php) classes have no equivalent in `silverstripe/linkfield`.

This guide will help you migrate to `silverstripe/linkfield` and run a task that will automatically update your data.

> [!WARNING]
> This guide and the associated migration task assume all of the data for your links are in the base table for `gorriecoe\Link\Models\Link` or in automatically generated tables (e.g. join tables for `many_many` relations).
> If you have subclassed `gorriecoe\Link\Models\Link`, there may be additional steps you need to take to migrate the data for your subclass.

## Setup

> [!TIP]
> We strongly recommend taking a backup of your database before doing anything else.
> This will ensure you have a known state to revert to in case anything goes wrong.

### Update your dependencies

Remove the gorriecoe modules and add `silverstripe/linkfield`:

```bash
composer remove gorriecoe/silverstripe-link gorriecoe/silverstripe-linkfield
composer require silverstripe/linkfield:^4
```

### Configure the migration task

> [!NOTE]
> Be sure to check how the old module classes are referenced in config `yml` files (eg: `app/_config`). Update appropriately.

1. Enable the task:

    ```yml
    SilverStripe\LinkField\Tasks\GorriecoeMigrationTask:
      is_enabled: true
    ```

1. Declare any columns that you added to the gorriecoe link model which need to be migrated to the new base link table, for example if you added a custom sort column for your `has_many` relations:

    ```yml
    SilverStripe\LinkField\Tasks\GorriecoeMigrationTask:
      # ...
      base_link_columns:
        MySortColumn: 'Sort'
    ```

1. Declare any `has_many` relations that need to be migrated:

    ```yml
    SilverStripe\LinkField\Tasks\GorriecoeMigrationTask:
      # ...
      has_many_links_data:
        # The class where the has_many relation is declared
        App\Model\MyClass:
          # The key is the name of the has_many relation
          # The value is the name of the old has_one relation on the gorriecoe link model
          LinkListOne: 'MyOwner'
    ```

1. Declare any `many_many` relations that need to be migrated:

    ```yml
    SilverStripe\LinkField\Tasks\GorriecoeMigrationTask:
      # ...
      many_many_links_data:
        # The class where the many_many relation is declared
        App\Model\MyClass:
          # If it's a normal many_many relation with no extra fields,
          # you can simply set the value to null and let the migration task figure it out
          LinkListExample: null
          # If the many_many is a many_many through, or had a $many_many_extraFields configuration defined,
          # you will need to provide additional information
          LinkListTwo:
            # The table is required for many_many through
            table: 'Page_ManyManyLinks'
            # Extra fields is for $many_many_extraFields, or for any $db fields on a
            # many_many through join model
            extraFields:
              MySort: 'Sort'
            # For many_many through relations, you must add the names of the has_one relations
            # from the DataObject which was used as the join model
            through:
              from: 'FromHasOneName'
              to:  'ToHasOneName'
    ```

1. Declare any classes that may have `has_one` relations to `Link`, but which do not *own* the link. Classes declared here will include any subclasses.
    For example if a custom link has a `has_many` relation to some class which does not own the link, declare that class here so it is not incorrectly identified as the owner of the link:

    ```yml
    SilverStripe\LinkField\Tasks\GorriecoeMigrationTask:
      # ...
      classes_that_are_not_link_owners:
        - App\Model\SomeClass
    ```

### Update your codebase

You should review how you are using the original `Link` model and `LinkField`, but if you don't have any customisations, then replacing the old with the new might be quite simple.

1. If you added any database columns to the `Link` class for sorting `has_many` relations, or any `has_one` relations for storing them, remove the extension or YAML configuration for that now.

    ```diff
    - gorriecoe\Link\Models\Link:
    -   db:
    -     MySortColumn: Int
    -   has_one:
    -     MyOwner: App\Model\MyClass
    -   belongs_many_many:
    -     BelongsRecord : App\Model\MyClass.LinkListTwo
    ```

1. Update use statements and relations for the classes which own links.
    - Any `many_many` relations should be swapped out for `has_many` relations, and all `has_many` relations should point to the `Owner` relation on the link class via dot notation.
    - If the models that have `has_one` or `has_many` relations to link don't already use the `$owns` configuration for those relations, add that now. You may also want to set `$cascade_deletes` and `$cascade_duplicates` configuration. See [basic usage](../01_basic_usage.md) for more details.

    ```diff
      namespace App\Model;

    - use gorriecoe\Link\Models\Link;
    - use gorriecoe\LinkField\LinkField;
    + use SilverStripe\LinkField\Models\Link;
    + use SilverStripe\LinkField\Form\LinkField;
    + use SilverStripe\LinkField\Form\MultiLinkField;
      use SilverStripe\ORM\DataObject;

      class MyClass extends DataObject
      {
          private static array $has_one = [
              'HasOneLink' => Link::class,
          ];

          private static array $has_many = [
    -         'LinkListOne' => Link::class . '.MyOwner',
    +         'LinkListOne' => Link::class . '.Owner',
    +         'LinkListTwo' => Link::class . '.Owner',
          ];

    +     private static array $owns = [
    +         'HasOneLink',
    +         'LinkListOne',
    +         'LinkListTwo',
    +     ];
    +
    -     private static array $many_many = [
    -         'LinkListTwo' => Link::class,
    -     ];
    -
    -     private static array $many_many_extraFields = [
    -         'LinkListTwo' => [
    -             'MySort' => 'Int',
    -         ]
    -     ];
      }
    ```

1. If you had `many_many` through relation, delete the `DataObject` class which was used as the join table.
1. Update the usage of link fields.

    ```diff
      public function getCMSFields()
      {
          $fields = parent::getCMSFields();
    +     $fields->removeByName(['HasOneLinkID', 'LinkListOne', 'LinkListTwo']);
          $fields->addFieldsToTab(
              'Root.Main',
              [
    -             LinkField::create('HasOneLink', 'Has one link', $this),
    -             LinkField::create('LinkListOne', 'List list one', $this)->setSortColumn('MySortColumn'),
    -             LinkField::create('LinkListTwo', 'Link list two', $this)->setSortColumn('MySort'),
    +             LinkField::create('HasOneLink', 'Has one link'),
    +             MultiLinkField::create('LinkListOne', 'List list one'),
    +             MultiLinkField::create('LinkListTwo', 'Link list two'),
              ]
          );
          return $fields;
      }
    ```

1. If you applied [linkfield configuration](https://github.com/elliot-sawyer/silverstripe-linkfield?tab=readme-ov-file#configuration), update that now.
    - See [configuring links and link fields](../02_configuration.md) for more information.

    ```diff
    + use SilverStripe\LinkField\Models\ExternalLink;
    + use SilverStripe\LinkField\Models\SiteTreeLink;

    - $linkConfig = [
    -     'types' => [
    -         'SiteTree',
    -         'URL',
    -     ],
    -     'title_display' => false,
    - ];
    - $linkField->setLinkConfig($linkConfig);
    + $allowedTypes = [
    +     SiteTreeLink::class,
    +     ExternalLink::class,
    + ];
    + $linkField->setAllowedTypes($allowedTypes);
    + $linkField->setExcludeLinkTextField(true);
    ```

## Customising the migration

There are many extension hooks in the [`GorriecoeMigrationTask`](api:SilverStripe\LinkField\Tasks\GorriecoeMigrationTask) class which you can use to change its behaviour or add additional migration steps. We strongly recommend taking a look at the source code to see if your use case requires any customisations.

Some scenarios where you may need customisations include:

- You had applied the [`Versioned`](api:SilverStripe\Versioned\Versioned) extension to `Link` and want to retain that versioning history
- You subclassed the base `Link` model and need to migrate data from your custom subclass
- You were relying on features of `gorriecoe/silverstripe-link` or `gorriecoe/silverstripe-linkfield` which don't have a 1-to-1 equivalent in `silverstripe/linkfield`

Other customisations you may be using that will require manual migration or implementation include:

- [gorriecoe/silverstripe-linkicon](https://github.com/gorriecoe/silverstripe-linkicon)
- [gorriecoe/silverstripe-ymlpresetlinks](https://github.com/gorriecoe/silverstripe-ymlpresetlinks)

### Custom links

If you have custom link implementations, you will need to implement an appropriate subclass of [`Link`](api:SilverStripe\LinkField\Models\Link) (or apply an extension to an existing one) with appropriate database columns and relations.

You'll also need to add configuration to `GorriecoeMigrationTask` so it knows how to handle the migration from the old link to the new one:

```yml
SilverStripe\LinkField\Tasks\GorriecoeMigrationTask:
  # ...
  link_type_columns:
    # The name of the Type for your custom type as defined in gorriecoe/Link/Models/Link.types
    MyCustomType:
      # The FQCN for your new custom link subclass
      class: 'App\Model\Link\MyCustomLink'
      # An mapping of column names from the gorriecoe link to your link subclass
      # Only include columns that are defined in the $db configuration for your subclass
      fields:
        MyOldField: 'MyNewField'
```

Some custom link implementations you may be using include:

- [gorriecoe/silverstripe-securitylinks](https://github.com/gorriecoe/silverstripe-securitylinks)
- [gorriecoe/silverstripe-directionslink](https://github.com/gorriecoe/silverstripe-directionslink)
- [gorriecoe/silverstripe-advancedemaillink](https://github.com/gorriecoe/silverstripe-advancedemaillinks)

## Migrating

For databases that support transactions, the full data migration is performed within a single transaction, and any errors in the migration will result in rolling back all changes. This means you can address whatever caused the error and then run the task again.

> [!NOTE]
> We strongly recommend running this task in a local development environment before trying it in production.
> There may be edge cases that the migration task doesn't account for which need to be resolved.

1. Build the database and flush your cache
    - via the browser: `https://www.example.com/dev/build?flush=1`
    - via a terminal: `sake db:build --flush`
1. Run the task
    - via the browser: `https://www.example.com/dev/tasks/gorriecoe-to-linkfield-migration-task`
    - via a terminal: `sake tasks:gorriecoe-to-linkfield-migration-task`

The task performs the following steps:

1. Inserts new rows into the base link table, taking values from the old link table.
1. Inserts new rows into tables for link subclasses, taking values from the old link table.
1. Updates `SiteTreeLink` records, splitting out the old `Anchor` column into the separate `Anchor` and `QueryString` columns.
1. Migrates any `has_many` relations which were declared in [`GorriecoeMigrationTask.has_many_links_data`](api:SilverStripe\LinkField\Tasks\GorriecoeMigrationTask->has_many_links_data).
1. Migrates any `many_many` relations which were declared in in [`GorriecoeMigrationTask.many_many_links_data`](api:SilverStripe\LinkField\Tasks\GorriecoeMigrationTask->many_many_links_data) and drops the old join tables.
1. Set the `Owner` relation for `has_one` relations to links.
1. Drops the old link table.
1. Publishes all links, unless you have removed the `Versioned` extension.
1. Output a table with any links which are lacking the data required to generate a URL.
    - You can skip this step by adding `?skipBrokenLinks=1` to the end of the URL: `https://www.example.com/dev/tasks/gorriecoe-to-linkfield-migration-task?skipBrokenLinks=1`.
    - If you're running the task in a terminal, you can add `--skipBrokenLinks` as an argument: `sake tasks:gorriecoe-to-linkfield-migration-task --skipBrokenLinks`.

> [!WARNING]
> If the same link appears in multiple `many_many` relation lists within the same relation (with different owner records), the link will be duplicated so that a single link exists for each `has_many` relation list.
>
> Unless you were doing something custom to manage links it's unlikely this will affect you - but if it does, just be aware of this and prepare your content authors for this change in their authoring workflow.
>
> If the same link appears in multiple `many_many` relation lists across *different* relations, you will need to handle the migration of this scenario yourself. The migration task will not duplicate these links. The link's owner will be whichever record is first identified, and any further owner records will simply not have that link in their `has_many` relation list.
