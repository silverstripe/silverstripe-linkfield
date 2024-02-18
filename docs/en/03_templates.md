---
title: Rendering links in templates
summary: How to render links in templates, and what templates to override
---

# Rendering links in templates

The easiest way to render a [`Link`](api:SilverStripe\LinkField\Models\Link) record from a `has_one` relation in a template is to use the relation name like so:

```ss
$HasOneLink
```

For links in a `has_many` relation, you can loop through the relation list and use the special `$Me` variable.

See [`forTemplate()` and `$Me`](https://docs.silverstripe.org/en/developer_guides/templates/syntax/#fortemplate) for details about how that works.

```ss
<% if $HasManyLinks %>
    <ul>
        <% loop $HasManyLinks %>
            <li>$Me</li>
        <% end_loop %>
    </ul>
<% end_if %>
```

If you want finer control of how a specific link is rendered, you can reference specific fields on the link like with any other relation:

```ss
<% with $HasOneLink %>
    <% if $exists %>
        <a href="$URL" <% if $OpenInNew %>target="_blank" rel="noopener noreferrer"<% end_if %>>$Title</a>
    <% end_if %>
<% end_with %>
```

```ss
<ul>
    <% loop $HasManyLinks %>
        <li>
            <a href="$URL" <% if $OpenInNew %>target="_blank" rel="noopener noreferrer"<% end_if %>>$Title</a>
        </li>
    <% end_loop %>
</ul>
```

## Custom templates

By default, links render using the `SilverStripe\LinkField\Models\Link.ss` template.

You can override that template to affect all links. You can also create a new template for any given `Link` subclass which will only be used by that subclass.

With the example file structure below, the [`EmailLink`](api:SilverStripe\LinkField\Models\EmailLink) model will use the custom `EmailLink.ss` template, and all other link types will use the custom `Link.ss` template.

```text
themes/my-theme/
├─ ...
└─ templates/
   ├─ Includes/
   ├─ Layouts/
   ├─ ...
   └─ SilverStripe/LinkField/Models/
      ├─ Link.ss
      └─ EmailLink.ss
```
