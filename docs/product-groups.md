# Package Product Groups

Capell groups first-party packages by customer-facing value. Composer names remain focused and stable; the product group controls how packages appear in catalogues, pricing, and marketplace screens.

## Capell Foundation

Free baseline packages:

| Package            | Composer name                   |
| ------------------ | ------------------------------- |
| Blog               | `capell-app/blog`               |
| Navigation         | `capell-app/navigation`         |
| Tags               | `capell-app/tags`               |
| Address            | `capell-app/address`            |
| Media Library      | `capell-app/media-library`      |
| Frontend Authoring | `capell-app/frontend-authoring` |
| Foundation Theme   | `capell-app/foundation-theme`   |

Tags and Media Library are Foundation packages because taxonomy and media management are normal CMS expectations. Redirect management is built into Capell Core/Admin rather than shipped as an add-on package.

## Premium Groups

| Product group         | Bundle key       | Packages                                                                                   |
| --------------------- | ---------------- | ------------------------------------------------------------------------------------------ |
| Capell Commercial     | `commercial`     | AIOrchestrator                                                                             |
| Capell FormBuilder    | `form-builder`   | FormBuilder                                                                                |
| Capell Publishing Pro | `publishing-pro` | PublishingStudio                                                                           |
| Capell Operations     | `operations`     | MigrationAssistant, Diagnostics, Login Audit                                               |
| Capell Growth         | `growth`         | Insights, CampaignStudio                                                                   |
| Capell Communications | `communications` | Email Studio                                                                               |
| Capell Search & SEO   | `search-seo`     | SEO Suite, Search; AI Discovery, llms.txt, page Markdown, crawler policy, readiness audits |
| Capell Themes         | `themes`         | Agency Theme, Corporate Theme, SaaS Theme                                                  |

## Manifest Fields

Every first-party package should expose:

```json
{
    "productGroup": "Capell Themes",
    "tier": "premium",
    "bundle": "themes"
}
```

Use stable bundle keys in code and marketplace syncs. Use product group names in user-facing UI and docs.

## Naming Rule

Do not rename Composer packages simply because they sell together. For example, `capell-app/migration-assistant`, `capell-app/diagnostics`, and `capell-app/login-audit` stay separate packages but group together as **Capell Operations**.

Email Studio uses **Capell Communications** because transactional email is a distinct operational surface. It supports Growth and FormBuilder workflows, but it should not be sold as a newsletter or campaign audience tool.
