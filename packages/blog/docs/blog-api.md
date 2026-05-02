# API Reference — Capell Blog

Browse `src/` for full source. This page is a map of the key entry points.

## Service provider

- `src/Providers/BlogServiceProvider.php` — registers models, resources, page schema, Livewire pages, widgets (if Layout present), sitemap extensions, and view components.

## Models

- `src/Models/Article.php` — article pages (`articles` table)
- `src/Models/Tag.php` — custom workspace-aware Tag
- `src/Models/Taggable.php` — explicit pivot model
- `src/Models/Concerns/HasTags.php` — reusable `tags()` morph-to-many concern

## Filament resources

### Articles (`src/Filament/Resources/Articles/`)

- `ArticleResource.php` + Create/Edit/List pages
- `Forms/ArticleForm.php`, `Forms/SettingsTab.php`
- `Tables/ArticlePagesTable.php`
- `Schemas/ArticlePageSchema.php`, `Schemas/ArticlePageSelectField.php`
- `Components/ArticleSelect.php`, `Components/TagsInput.php`

### Tags (`src/Filament/Resources/Tags/`)

- `TagResource.php` + Create/Edit/List pages
- `Forms/TagForm.php`
- `Tables/TagsTable.php`
- `RelationManagers/PagesRelationManager.php`
- `Widgets/ListArticlesWidget.php`
- `Widgets/Schemas/ArticleWidgetSchema.php`, `Widgets/Schemas/RelatedWidgetSchema.php`

## Livewire pages

Under `src/Livewire/Page/`:

- `Blog.php` — article index
- `Archive.php` — `{year}/{month}` archive filter
- `Tag.php` — filter by tag slug

## Sitemap integration

Under `src/Support/Sitemap/`:

- `ArticlesSitemap.php` — emits article URLs
- `ArchivesSitemap.php` — emits archive URLs
- `TagsSitemap.php` — emits tag URLs

## Actions

Under `src/Actions/`:

- `CreateBlogPagesAction` — bulk-create the three default pages for a site
- `InstallPackageAction` — publish migrations, run them, register resources
- `GetArticleLayoutAction` — resolve the layout assigned to an article
- `GenerateArchiveUrl` — build archive URLs from a date

## Navigation listener

- `src/Listeners/AddBlogPagesToNavigation.php` — subscribes to the admin's `NavigationCreating` event to surface Blog/Archives/Tags entries.

## Enums

`src/Enums/*` — schema and component identifiers used by the Filament and Layout integration.

## Commands

Under `src/Console/Commands/`:

- `InstallCommand` — `capell:blog-install`
- `SetupCommand` — `capell:blog-setup`
- `CreateBlogPagesCommand` — `capell:blog-create-pages {site}`
- `DemoCommand` — `capell:blog-demo`

## Composer dependencies

- `capell-app/admin`
- `capell-app/frontend`
- `filament/spatie-laravel-tags-plugin`

## Quick links

- Source directory: [`./src`](../src)
- Database reference: [blog-database.md](blog-database.md)
- Package README: [../README.md](../README.md)
