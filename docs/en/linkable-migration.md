# Instructions

## Preamble

This migration process covers shifting data from the `Linkable` tables to the appropriate `LinkField` tables.

This does not cover usages of `EmbeddedObject` (at least, not at this time).

**Versioned:** If you have `Versioned` `Linkable`, then the expectation is that you will also `Version` `LinkField`. If
you have not `Versioned` `Linkable`, then the expectation is that you will **not** `Version` `LinkField`.

## Install Silvesrtripe Linkfield

Install the Silverstripe Linkfield module:

```bash
$ composer require silverstripe/linkfield 1.x-dev
```

Or if you would like the (experimental) GraphQL 4 version:

```bash
$ composer require silverstripe/linkfield 2.x-dev
```

Optionally, you can also remove the Linkable module (though, you might find it useful to keep around as a reference
while you are upgrading your code).

Do this step at whatever point makes sense to you.

```bash
$ composer remove sheadawson/silverstripe-linkable
```

## Replace app usages

You should review how you are using the original `Link` model and `LinkField`, but if you don't have any customisations,
then replacing the old with the new **might** be quite simple.

If you have used imports (`use` statements), then your first step might just be to search for `use [old];` and replace
with `use [new];` (since the class name references have not changed at all).

Old: `Sheadawson\Linkable\Models\Link`
New: `SilverStripe\LinkField\Models\Link`

Old: `Sheadawson\Linkable\Forms\LinkField`
New: `SilverStripe\LinkField\Form\LinkField`

If you have extensions, new fields, etc, then your replacements might need to be a bit more considered.

The other key (less easy to automate) thing that you'll need to update is that the old `LinkField` required you to
specify the related field with `ID` appended, whereas the new `LinkField` requires you to specify the field without
`ID` appended. EG.

Old: `LinkField::create('MyLinkID')`
New: `LinkField::create('MyLink')`

Search for instances of `LinkField::create` and `new LinkField`, and hopefully that should give you all of the places
where you need to update field name references.

### Configuration

Be sure to check how the old module classes are referenced in config `yml` files (eg: `app/_config`). Update
appropriately.

### Populate module

If you use the populate module, you will not be able to simply "replace" the namespace. Fixture definitions for the
new Linkfield module are quite different. There are entirely different models for different link types, whereas before
it was just a DB field to specify the type.

## Replace template usages

Before: You might have had references to `$LinkURL` or `$Link.LinkURL`.
After: These would need to be updated to `$URL` or `$Link.URL` respectively.

Before: `$OpenInNewWindow` or `$Link.OpenInNewWindow`.
After: `$OpenInNew` or `$Link.OpenInew` respectively.

Before: `$Link.TargetAttr` or `$TargetAttr` would output the appropriate `target="xx"`.
After: There is no direct replacement.

This is an area where you should spend some decent effort to make sure each implementation is outputting as you expect
it to. There may be more "handy" methods that Linkable provided that no longer exist (that we haven't covered above).

## Table structures

It's important to understand that we are going from a single table in Linkable to multiple tables in LinkField.

**Before:** We had 1 table with all data, and one of the field in there specified the type of the Link.
**Now:** We have 1 table for each type of Link, with a base `Link` table for all record.

## Specify any custom configuration

Have a look at `LinkableMigrationTask`. There are some configuration properties defined in there:

- `$link_mapping`
- `$email_mapping`
- `$external_mapping`
- `$file_mapping`
- `$phone_mapping`
- `$sitetree_mapping`

Each of these specifies how an original field from the `LinkableLink` table will map to one of the new LinkField tables.

If you previously had some custom fields that needed to be available across **all** Link types, then you're (probably)
going to add this as an extension on the (base) `Link` class. This is going to mean that the new fields will be added
to the `LinkField_Link` table. This means that you need to update the configuration for `$link_mapping` so that we
correctly migrate those field values into the `LinkField_Link` table.

If you had/have a field that you only want displayed on (say) SiteTree links, then you would want to add that extension
to `SiteTreeLink`. This would create new fields in the `LinkField_SiteTreeLink` table, which will mean you need to
also update the config for `$sitetree_mapping`.

It's important that you get the correct mappings to the correct tables.
