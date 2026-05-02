# Capell Tags

**Product group:** Capell Foundation
**Tier:** Free

Tags provides a reusable tagging taxonomy for Capell packages. Blog uses it for articles, and other packages can opt their own models into the same tag model.

## When to install it

Install Tags when multiple content types need shared labels, filters, archives, or taxonomy-style organization.

## Quick install

```bash
composer require capell-app/tags
php artisan capell:tags-install
php artisan optimize:clear
```

## What appears in the admin

| Area             | What editors can do                                |
| ---------------- | -------------------------------------------------- |
| Tags             | Manage reusable tags                               |
| Tagged resources | Attach tags through package resources such as Blog |

## What developers get

- `Capell\Tags\Models\Tag` and `Taggable`.
- `HasTags` model concern.
- `TagTypeEnum` for separating tag use cases.
- Admin and console providers for package registration.
