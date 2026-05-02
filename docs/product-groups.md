# Package Product Groups

Capell groups first-party packages by customer-facing value. Composer names remain focused and stable; the product group controls how packages appear in catalogues, pricing, and marketplace screens.

## Capell Foundation

Free baseline packages:

| Package          | Composer name                 |
| ---------------- | ----------------------------- |
| Mosaic           | `capell-app/mosaic`           |
| Blog             | `capell-app/blog`             |
| Navigation       | `capell-app/navigation`       |
| Tags             | `capell-app/tags`             |
| Redirects        | `capell-app/redirects`        |
| Address          | `capell-app/address`          |
| Media Curator    | `capell-app/media-curator`    |
| Frontend Toolbar | `capell-app/frontend-toolbar` |
| HTML Minify      | `capell-app/html-minify`      |
| Default Theme    | `capell-app/default-theme`    |

Tags and Media Curator are Foundation packages because taxonomy and media management are normal CMS expectations.

## Premium Groups

| Product group         | Bundle key       | Packages                                                                         |
| --------------------- | ---------------- | -------------------------------------------------------------------------------- |
| Capell Commercial     | `commercial`     | Assistant                                                                        |
| Capell Forms          | `forms`          | Forms                                                                            |
| Capell Publishing Pro | `publishing-pro` | Workspaces, Filament Peek                                                        |
| Capell Operations     | `operations`     | Backup, Developer Tools, Authentication Log                                      |
| Capell Growth         | `growth`         | Analytics, Campaigns                                                             |
| Capell Search & SEO   | `search-seo`     | SEO Tools, Site Search                                                           |
| Capell Theme Studio   | `theme-studio`   | Theme Studio Core, Theme Studio Admin, Agency Theme, Corporate Theme, SaaS Theme |

## Manifest Fields

Every first-party package should expose:

```json
{
    "productGroup": "Capell Theme Studio",
    "tier": "premium",
    "bundle": "theme-studio"
}
```

Use stable bundle keys in code and marketplace syncs. Use product group names in user-facing UI and docs.

## Naming Rule

Do not rename Composer packages simply because they sell together. For example, `capell-app/backup`, `capell-app/developer-tools`, and `capell-app/authentication-log` stay separate packages but group together as **Capell Operations**.
