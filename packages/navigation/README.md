# Capell Navigation

**Product group:** Capell Foundation
**Tier:** Free

Navigation gives Capell a first-party menu system for headers, footers, sidebars, and package-defined link trees.

## When to install it

Install Navigation when editors need to manage menus from the admin instead of hard-coding links in Blade.

## Quick install

```bash
composer require capell-app/navigation
php artisan capell:navigation-setup
php artisan capell:navigation-demo
```

Both setup and demo commands can prompt for sites. Use `--sites="Site Name"` for non-interactive runs.

## What appears in the admin

| Area        | What editors can do                                          |
| ----------- | ------------------------------------------------------------ |
| Navigation  | Manage named menus such as header, footer, and sidebar       |
| Site editor | Configure navigation for each site                           |
| Page editor | Add or remove pages from navigation through schema extenders |

## What developers get

- Navigation models, enums, loaders, and cache keys.
- `NavigationItemData` for structured menu items.
- Actions for adding, removing, resolving, and replicating navigation items.
- Registry hooks for custom navigable models.
