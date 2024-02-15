---
title: Gotchas
summary: Behaviour or scenarios that might be unexpected, and how to deal with them
---

# Gotchas

This page details behaviour or scenarios that might be unexpected, and how to deal with them.

## Linked pages and files are not automatically published

When publishing a link that is either a "Page on this site" or a "Link to a file", the page or file that is linked to will not be automatically published when the link is published. This is to prevent unintentional publishing of draft or modified pages and files. However this does mean that content authors may publish links to unpublished pages or files which will return a 404 when a regular user attempts to use the link.

The link is itself published when the link's "owner" is published. The links owner is a page or another `DataObject` that the link has been added to as a relation.

If you wish to have pages and files that are linked to be automatically published when the link is published, then add the following YAML configuration in your project:

```yml
# "Page on this site"
SilverStripe\LinkField\Models\SiteTreeLink:
  owns:
    - Page

# "Link to a file"
SilverStripe\LinkField\Models\FileLink:
  owns:
    - File
```

Read more about [defining ownership between related objects](https://docs.silverstripe.org/en/developer_guides/model/versioning/#defining-ownership-between-related-versioned-dataobject-models).
