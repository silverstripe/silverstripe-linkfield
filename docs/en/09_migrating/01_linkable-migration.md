---
title: Migrating from sheadawson/silverstripe-linkable module
summary: A guide for migrating from sheadawson/silverstripe-linkable to silverstripe/linkfield
---

# Migrating from sheadawson/silverstripe-linkable module

> [!NOTE]
> If your site is running Silverstripe CMS 4.x, upgrade to CMS 5 first.
> You will most likely need to use a fork of `sheadawson/silverstripe-linkable` that is compatible with Silverstripe CMS 5 as part of this upgrade.
> Once you have finished upgrading to CMS 5, return to this guide and continue the linkfield upgrade.

The [`sheadawson/silverstripe-linkable` module](https://github.com/sheadawson/silverstripe-linkable) was a much loved, and much used module. It is, unfortunately, no longer maintained. We have provided some steps and tasks that we hope can be used to migrate your project from Linkable to LinkField.

There are a few major changes between `sheadawson/silverstripe-linkable` and `silverstripe/linkfield`:

1. Link types are defined via subclasses in `silverstripe/linkfield` as opposed to configuration within a single model.
1. `silverstripe/linkfield` doesn't support `many_many` relations - these will be migrated to `has_many` relations instead.
1. Many fields and relations have different names.
1. The default title for a link isn't stored in the database - if the `LinkText` field is left blank, nothing gets stored in the database for that field.
    - This means any links migrated which had the default title set will be migrated with that title as explicit `LinkText`, which will not update automatically when you change the link URL.
    - If you want the `LinkText` for those links to update automatically, you will need to either [customise the migration](#customising-the-migration) or manually unset the `LinkText` for those links afterward.

The following additional items were identified as feature gaps, which may require some additional work to implement if you require them:

- Adding custom CSS classes to link. The `setCSSClass()` method does not exist in Linkfield. You can still add an `Extension` to the `Link` class or develop your own custom link class and implement the logic of this method.
- Customizing link templates. You can still call the `renderWith()` method and pass the name of your custom template, or use a template with the file path of the FQCN of the link subclass, but `LinkField` doesn't support the `templates` configuration.
- Limit allowed Link types. The `silverstripe/linkfield` module does not support the `allowed_types` configuration. Now, in order to set a limited list of link types available to the user, you should use the `LinkField::setAllowedTypes()` method. Use `allowed_by_default` configuration to globally limit link types.
- Custom query params. This functionality is not supported. You can no longer set the `data-extra-query` attribute to a link. But you can still add an extension to the link and template that will allow you to implement this logic.
- The `EmbeddedObject` and `EmbeddedObjectField` classes have no equivalent functionality in `silverstripe/linkfield`
- If you have subclassed `Sheadawson\Linkable\Models\Link`, there may be additional steps you need to take to migrate the data for your subclass.

> [!WARNING]
> This guide and the associated migration task assume all of the data for your links are in the base table for `Sheadawson\Linkable\Models\Link` or in automatically generated tables (e.g. join tables for `many_many` relations).

## Setup

> [!TIP]
> We strongly recommend taking a backup of your database before doing anything else.
> This will ensure you have a known state to revert to in case anything goes wrong.

### Update your dependencies

Remove the Linkable module and add `silverstripe/linkfield`:

```bash
composer remove sheadawson/silverstripe-linkable
composer require silverstripe/linkfield:^4
```

### Configure the migration task

> [!NOTE]
> Be sure to check how the old module classes are referenced in config `yml` files (eg: `app/_config`). Update appropriately.

1. Enable the task:

    ```yml
    SilverStripe\LinkField\Tasks\LinkableMigrationTask:
      is_enabled: true
    ```

> [!WARNING]
> The sheadawson/silverstripe-linkable documentation does not provide guidance or advice on setting up and maintaining `has_many` and `many_many` link relationships. This guide and the corresponding migration task only make an assumption how this setting was made. It is your responsibility to check that this assumption suits your case and customising the migration task as required.

1. Declare any columns that you added to the linkable link model which need to be migrated to the new base link table, for example if you added a custom sort column for your `has_many` relations:

    ```yml
    SilverStripe\LinkField\Tasks\LinkableMigrationTask:
      # ...
      base_link_columns:
        MySortColumn: 'Sort'
    ```

1. Declare any `has_many` relations that need to be migrated:

    ```yml
    SilverStripe\LinkField\Tasks\LinkableMigrationTask:
      # ...
      has_many_links_data:
        # The class where the has_many relation is declared
        App\Model\MyClass:
          # The key is the name of the has_many relation
          # The value is the name of the old has_one relation on the Linkable link model
          LinkListOne: 'MyOwner'
    ```

1. Declare any `many_many` relations that need to be migrated:

    ```yml
    SilverStripe\LinkField\Tasks\LinkableMigrationTask:
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
              from: 'FromHasOneName',
              to:  'ToHasOneName',
    ```

1. Declare any classes that may have `has_one` relations to `Link`, but which do not *own* the link. Classes declared here will include any subclasses.
    For example if a custom link has a `has_many` relation to some class which does not own the link, declare that class here so it is not incorrectly identified as the owner of the link:

    ```yml
    SilverStripe\LinkField\Tasks\LinkableMigrationTask:
      # ...
      classes_that_are_not_link_owners:
        - App\Model\SomeClass
    ```

### Update your codebase

You should review how you are using the original `Link` model and `LinkField`, but if you don't have any customisations, then replacing the old with the new might be quite simple.

1. If you added any database columns to the `Link` class for sorting `has_many` relations, or any `has_one` relations for storing them, remove the extension or YAML configuration for that now.

    ```diff
    - Sheadawson\Linkable\Models\Link:
    -   db:
    -     MySortColumn: Int
    -   has_one:
    -     MyOwner: App\Model\MyClass
    -   belongs_many_many:
    -     BelongsRecord : App\Model\MyClass.LinkListTwo
    ```

1. Update use statements and relations for the classes which own links.
    - Any `many_many` relations should be swapped out for `has_many` relations, and all `has_many` relations should point to the `Owner` relation on the link class via dot notation.
    - If the models that have `has_one` or `has_many` relations to link don't already use the `$owns` configuration for those relations, add that now. You may also want to set `$cascade_deletes` and `$cascade_duplicates` configuration. See [basic usage](../01_basic_usage.md) for more details.ed.

    ```diff
      namespace App\Model;

    - use Sheadawson\Linkable\Models\Link;
    - use Sheadawson\Linkable\Forms\LinkField;
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
1. Update the usage of link field (and `GridField` if you were using that to manage `has_many` or `many_many` relations).
    - Note that the linkable module's `LinkField` required you to specify the related field with `ID` appended (e.g. `HasOneLinkID`), whereas the new `LinkField` requires you to specify the field without `ID` appended (e.g. `HasOneLink`).

    ```diff
      public function getCMSFields()
      {
          $fields = parent::getCMSFields();
    +     $fields->removeByName(['HasOneLinkID', 'LinkListOne', 'LinkListTwo']);
          $fields->addFieldsToTab(
              'Root.Main',
              [
    -             LinkField::create('HasOneLinkID', 'Has one link')
    +             LinkField::create('HasOneLink', 'Has one link'),
    -             GridField::create(
    -                 'LinkListTwo',
    -                 'Link List Two',
    -                 $this->LinkListTwo(),
    -                 GridFieldConfig_RelationEditor::create()
    -                   ->removeComponentsByType(GridFieldAddExistingAutocompleter::class)
    -             ),
    +             MultiLinkField::create('LinkListOne', 'List list one'),
    +             MultiLinkField::create('LinkListTwo', 'List list two'),
              ]
          );
          return $fields;
      }

    ```

1. In `sheadawson/silverstripe-linkable`, the list of allowed link types was listed in the configuration file. `LinkField` uses a different approach, it is necessary to specify in the configuration those types of links that will be unavailable to the user. If you need to make a certain type of link available, you must use the `LinkField::setAllowedTypes()` method and pass an array of class names as a parameter. Use `allowed_by_default` if it's needed to globally limit link types.
    - See [configuring links and link fields](../02_configuration.md) for more information.

    ```diff
    - Sheadawson\Linkable\Models\Link:
    -   allowed_types:
    -     - URL
    -     - SiteTree

    // Now you should exclude all link types that are not allowed
    + SilverStripe\LinkField\Models\EmilLink:
    +  allowed_by_default: false
    + SilverStripe\LinkField\Models\PhoneLink:
    +  allowed_by_default: false
    + SilverStripe\LinkField\Models\FileLink:
    +  allowed_by_default: false

    ```

    ```diff
    + use SilverStripe\LinkField\Models\ExternalLink;
    + use SilverStripe\LinkField\Models\SiteTreeLink;

    - $allowedTypes = [
    -     'SiteTree',
    -     'URL',
    - ];

    + $allowedTypes = [
    +     SiteTreeLink::class,
    +     ExternalLink::class,
    + ];
      $linkField->setAllowedTypes($allowedTypes);
    ```

### Populate module

If you use the `dnadesign/silverstripe-populate` module, you will not be able to simply "replace" the namespace. Fixture definitions for the new Linkfield module are quite different. There are entirely different models for different link types, whereas before it was just a DB field to specify the type.

See below for example:

```diff
- Sheadawson\Linkable\Models\Link:
-   internal:
-     Title: Internal link
-     Type: SiteTree
-     SiteTreeID: 1
-     OpenInNewWindow: true
+ SilverStripe\LinkField\Models\SiteTreeLink:
+   internal:
+     LinkText: Internal link
+     Page: =>Page.home
+     OpenInNew: true
-  external:
-    Title: External link
-    Type: URL
-    URL: https://example.org
-    OpenInNewWindow: true
+ SilverStripe\LinkField\Models\ExternalLink:
+   external:
+     LinkText: External link
+     ExternalUrl: https://example.org
+     OpenInNew: true
- file:
-   Title: File link
-   Type: File
-   File: =>SilverStripe\Assets\File.example
+ SilverStripe\LinkField\Models\FileLink:
+   file:
+     LinkText: File link
+     File: =>SilverStripe\Assets\File.example
- phone:
-   Title: Phone link
-   Type: Phone
-   Phone: +64 1 234 567
+ SilverStripe\LinkField\Models\PhoneLink:
+   phone:
+     LinkText: Phone link
+     Phone: +64 1 234 567
- email:
-   Title: Email link
-   Type: Email
-   Email: foo@example.org
+ SilverStripe\LinkField\Models\EmailLink:
+   email:
+     LinkText: Email link
+     Email: foo@example.org

```

## Replace template usages

The link element structure is rendered using the `SilverStripe/LinkField/Models/Link.ss` template. You can override this template by copying it to the theme or project folder and making the necessary changes. You still can also specify a custom template to display the link by using the `renderWith()` method and passing the name of your custom template.
You can also provide templates for specific subclasses of `Link` - the file path for those templates is the FQCN for the link.

When working on your own template, you should consider the following differences in variable names.

**Before:** You might have had references to `$LinkURL` or `$Link.LinkURL`.\
**After:** These would need to be updated to `$URL` or `$Link.URL` respectively.

**Before:** `$OpenInNewWindow` or `$Link.OpenInNewWindow`.\
**After:** `$OpenInNew` or `$Link.OpenInNew` respectively.

**Before:** `$Link.TargetAttr` or `$TargetAttr` would output the appropriate `target="xx"`.\
**After:** There is no direct replacement.

## Customising the migration

There are many extension hooks in the [`LinkableMigrationTask`](api:SilverStripe\LinkField\Tasks\LinkableMigrationTask) class which you can use to change its behaviour or add additional migration steps. We strongly recommend taking a look at the source code to see if your use case requires any customisations.

Some scenarios where you may need customisations include:

- You had applied the [`Versioned`](api:SilverStripe\Versioned\Versioned) extension to `Link` and want to retain that versioning history
- You subclassed the base `Link` model and need to migrate data from your custom subclass
- You were relying on features of `sheadawson/silverstripe-linkable` or `sheadawson/silverstripe-linkable` which don't have a 1-to-1 equivalent in `silverstripe/linkfield`

### Custom links

If you have custom link implementations, you will need to implement an appropriate subclass of [`Link`](api:SilverStripe\LinkField\Models\Link) (or apply an extension to an existing one) with appropriate database columns and relations.

You need to update configuration `LinkableMigrationTask` so it knows how to handle the migration from the old link to the new one:

```yml
SilverStripe\LinkField\Tasks\LinkableMigrationTask:
  # ...
  link_type_columns:
    # The name of the Type for your custom type as defined in =====
    MyCustomType:
      # The FQCN for your new custom link subclass
      class: 'App\Model\Link\MyCustomLink'
      # An mapping of column names from the old link to your new link subclass
      # Only include columns that are defined in the $db configuration for your subclass
      fields:
        MyOldField: 'MyNewField'
```

## Migrating

> [!NOTE]
> This migration process covers shifting data from the `LinkableLink` tables to the appropriate `LinkField` tables.

For databases that support transactions, the full data migration is performed within a single transaction, and any errors in the migration will result in rolling back all changes. This means you can address whatever caused the error and then run the task again.

> [!NOTE]
> We strongly recommend running this task in a local development environment before trying it in production.
> There may be edge cases that the migration task doesn't account for which need to be resolved.

1. Run dev/build and flush your cache (use the method you will be using the for next step - i.e. if you're running the task via the terminal, make sure to flush via the terminal)
    - via the browser: `https://www.example.com/dev/build?flush=1`
    - via a terminal: `sake dev/build flush=1`
1. Run the task
    - via the browser: `https://www.example.com/dev/tasks/linkable-to-linkfield-migration-task`
    - via a terminal: `sake dev/tasks/linkable-to-linkfield-migration-task`

The task performs the following steps:

1. Inserts new rows into the base link table, taking values from the old link table.
1. Inserts new rows into tables for link subclasses, taking values from the old link table.
1. Updates `SiteTreeLink` records, splitting out the old `Anchor` column into the separate `Anchor` and `QueryString` columns.
1. Migrates any `has_many` relations which were declared in [`LinkableMigrationTask.has_many_links_data`](api:SilverStripe\LinkField\Tasks\LinkableMigrationTask->has_many_links_data).
1. Migrates any `many_many` relations which were declared in in [`LinkableMigrationTask.many_many_links_data`](api:SilverStripe\LinkField\Tasks\LinkableMigrationTask->many_many_links_data) and drops the old join tables.
1. Set the `Owner` relation for `has_one` relations to links.
1. Drops the old link table.
1. Publishes all links, unless you have removed the `Versioned` extension.
1. Output a table with any links which are lacking the data required to generate a URL.
    - You can skip this step by adding `?skipBrokenLinks=1` to the end of the URL: `https://www.example.com/dev/tasks/linkable-to-linkfield-migration-task?skipBrokenLinks=1`.
    - If you're running the task in a terminal, you can add `skipBrokenLinks=1` as an argument: `sake dev/tasks/linkable-to-linkfield-migration-task skipBrokenLinks=1`.

> [!WARNING]
> If the same link appears in multiple `many_many` relation lists within the same relation (with different owner records), the link will be duplicated so that a single link exists for each `has_many` relation list.
>
> Unless you were doing something custom to manage links it's unlikely this will affect you - but if it does, just be aware of this and prepare your content authors for this change in their authoring workflow.
>
> If the same link appears in multiple `many_many` relation lists across *different* relations, you will need to handle the migration of this scenario yourself. The migration task will not duplicate these links. The link's owner will be whichever record is first identified, and any further owner records will simply not have that link in their `has_many` relation list.
