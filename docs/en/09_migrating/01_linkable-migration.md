---
title: Migrating from Shae Dawson's Linkable module
summary: A guide for migrating from sheadawson/silverstripe-linkable to silverstripe/linkfield
---

# Migrating from Shae Dawson's Linkable module

The [`sheadawson/silverstripe-linkable` module](https://github.com/sheadawson/silverstripe-linkable) was a much loved, and much used module. It is, unfortunately, no longer maintained. We have provided some steps and tasks that we hope can be used to migrate your project from Linkable to LinkField.

> [!WARNING]
> This guide and the associated migration task assume all of the data for your links are in the base table for `Sheadawson\Linkable\Models\Link` or in automatically generated tables (e.g. join tables for `many_many` relations).
> If you have subclassed `Sheadawson\Linkable\Models\Link`, there may be additional steps you need to take to migrate the data for your subclass.

## Preamble

This migration process covers shifting data from the `Linkable` tables to the appropriate `LinkField` tables. This does not cover usages of `EmbeddedObject`.

**Versioned:** If you have `Versioned` `Linkable`, then the expectation is that you will also `Version` `LinkField`. If you have not `Versioned` `Linkable`, then the expectation is that you will **not** `Version` `LinkField`.

**No support for internal links with query params (GET params):** Please be aware that Linkfield does not support internal links with query params (`?`) out of the box, and therefor the migration task will **remove** any query params that are present in the Linkable's `Anchor` field.

## Install Silvesrtripe Linkfield

Install the Silverstripe Linkfield module:

```bash
composer require silverstripe/linkfield 4
```

Optionally, you can also remove the Linkable module (though, you might find it useful to keep around as a reference while you are upgrading your code).

Do this step at whatever point makes sense to you.

```bash
composer remove sheadawson/silverstripe-linkable
```

## Replace app usages

You should review how you are using the original `Link` model and `LinkField`, but if you don't have any customisations, then replacing the old with the new **might** be quite simple.

If you have used imports (`use` statements), then your first step might just be to search for `use [old];` and replace
with `use [new];` (since the class name references have not changed at all).
```diff
- Sheadawson\Linkable\Models\Link
+ SilverStripe\LinkField\Models\Link

- Sheadawson\Linkable\Forms\LinkField
+ SilverStripe\LinkField\Form\LinkField

```

If you have extensions, new fields, etc, then your replacements might need to be a bit more considered.

The other key (less easy to automate) thing that you'll need to update is that the old `LinkField` required you to specify the related field with `ID` appended, whereas the new `LinkField` requires you to specify the field without `ID` appended. EG.
```diff
- LinkField::create('MyLinkID')
+ LinkField::create('MyLink')
```
Search for instances of `LinkField::create` and `new LinkField`, and hopefully that should give you all of the places where you need to update field name references.

### Configuration

Be sure to check how the old module classes are referenced in config `yml` files (eg: `app/_config`). Update appropriately.

### Populate module

If you use the populate module, you will not be able to simply "replace" the namespace. Fixture definitions for the new Linkfield module are quite different. There are entirely different models for different link types, whereas before it was just a DB field to specify the type.

See below for example before/after usage:

#### Before

```yml
Sheadawson\Linkable\Models\Link:
  internal:
    Title: Internal link
    Type: SiteTree
    SiteTreeID: 1
    OpenInNewWindow: true
  external:
    Title: External link
    Type: URL
    URL: https://example.org
    OpenInNewWindow: true
  file:
    Title: File link
    Type: File
    File: =>SilverStripe\Assets\File.example
  phone:
    Title: Phone link
    Type: Phone
    Phone: +64 1 234 567
  email:
    Title: Email link
    Type: Email
    Email: foo@example.org
```

#### After

```yml
SilverStripe\LinkField\Models\SiteTreeLink:
  internal:
    LinkText: Internal link
    Page: =>Page.home
    OpenInNew: true
SilverStripe\LinkField\Models\ExternalLink:
  external:
    LinkText: External link
    ExternalUrl: https://example.org
    OpenInNew: true
SilverStripe\LinkField\Models\FileLink:
  file:
    LinkText: File link
    File: =>SilverStripe\Assets\File.example
SilverStripe\LinkField\Models\PhoneLink:
  phone:
    LinkText: Phone link
    Phone: +64 1 234 567
SilverStripe\LinkField\Models\EmailLink:
  email:
    LinkText: Email link
    Email: foo@example.org
```

## Replace template usages

**Before:** You might have had references to `$LinkURL` or `$Link.LinkURL`.
**After:** These would need to be updated to `$URL` or `$Link.URL` respectively.

**Before:** `$OpenInNewWindow` or `$Link.OpenInNewWindow`.
**After:** `$OpenInNew` or `$Link.OpenInNew` respectively.

**Before:** `$Link.TargetAttr` or `$TargetAttr` would output the appropriate `target="xx"`.
**After:** There is no direct replacement.

This is an area where you should spend some decent effort to make sure each implementation is outputting as you expect it to. There may be more "handy" methods that Linkable provided that no longer exist (that we haven't covered above).

## Table structures

It's important to understand that we are going from a single table in Linkable to multiple tables in LinkField.

**Before:** We had 1 table with all data, and one of the field in there specified the type of the Link.
**After:** We have 1 table for each type of Link, with a base `Link` table for all record.

## Migrating

The migration process completely repeats the process of updating the old version of Linkfield to the new one, so please follow the instructions provided in the [Migrating section](./00_upgrading.md#migrating) making the following small changes to the configuration files.

- Change Task class name from `SilverStripe\LinkField\Tasks\LinkFieldMigrationTask` to `SilverStripe\LinkField\Tasks\LinkableMigrationTask` in each yml configuration file.
