# Blog

Status: **Available, schema-owning** · Kind: **package** · Tier: **free** · Bundle: **foundation** · Contexts: **admin, frontend, console** · Product group: **Capell Foundation**

## What This Plugin Adds

Blog adds article publishing, archive pages, tag pages, article widgets, sitemaps, and frontend Livewire page components to Capell.

- Article Filament resource.
- Blog, archive, and tag frontend Livewire components.
- Article widgets and configurators for LayoutBuilder.
- Sitemap extensions for articles, archives, and tags.
- Commands to install and create blog pages.

## Why It Matters

**For developers:** Builds on core pages, layouts, translations, page URLs, LayoutBuilder widgets, and tags while keeping article-specific logic in actions and loaders.

**For teams:** Gives editors a dedicated article workflow that still fits the same structured publishing foundation as pages.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Insights](../insights/README.md)
- [Capell Frontend](https://github.com/capell-app/frontend)
- [Capell Layout Builder](../layout-builder/README.md)
- [Capell Navigation](../navigation/README.md)
- [Capell Tags](../tags/README.md)
- [Capell Publishing Studio](../publishing-studio/README.md)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- Articles admin index.
- Create/edit article form.
- Blog page frontend output.
- Archive page frontend output.
- Tag page frontend output.

## Technical Shape

- BlogServiceProvider, AdminServiceProvider, ConsoleServiceProvider, and FrontendServiceProvider register package surfaces.
- Migration creates articles.
- Model: Article.
- Filament resource: ArticleResource.
- Livewire pages: Blog, Archive, Tag.
- Listeners sync navigation and translation changes.

## Data Model

- articles stores uuid, workspace, type, layout, site, meta, visible_from, and visible_until.
- Articles connect to sites, types, layouts, page URLs, translations, LayoutBuilder widget assets, and tags.
- Blog requires LayoutBuilder before install.
- Deletion and retention behaviour should be verified against the host application policy.

## Install Impact

- Adds articles table and article admin resource.
- Adds blog frontend components and sitemap extensions.
- Adds console commands for setup, install, demo, faker, and page creation.
- Requires LayoutBuilder package first.
- May add blog pages to navigation through listener behaviour.

## Commands

- `capell:blog-create-pages {site : The ID of the site to create blog pages for}` (packages/blog/src/Console/Commands/CreateBlogPagesCommand.php)
- `capell:blog-demo {--sites=} {--user=} {--limit=}` (packages/blog/src/Console/Commands/DemoCommand.php)
- `capell:blog-faker {--count=25} {--sites=} {--languages=} {--force}` (packages/blog/src/Console/Commands/FakerCommand.php)
- `capell:blog-install` (packages/blog/src/Console/Commands/InstallCommand.php)
- `capell:blog-setup {--user= : Ignored — accepted for compatibility with capell:install} {--sites= : Ignored — accepted for compatibility with capell:install} {--languages= : Ignored — accepted for compatibility with capell:install} {--url= : Ignored — accepted for compatibility with capell:install}` (packages/blog/src/Console/Commands/SetupCommand.php)

## Admin And Access

- ArticleResource (packages/blog/src/Filament/Resources/Articles/ArticleResource.php, slug `article`)
- CreateArticle (packages/blog/src/Filament/Resources/Articles/Pages/CreateArticle.php)
- EditArticle (packages/blog/src/Filament/Resources/Articles/Pages/EditArticle.php)
- ListArticles (packages/blog/src/Filament/Resources/Articles/Pages/ListArticles.php)

- Gate: ArticleHealthWidgetAbstract: `developer`, `admin`, `super_admin`
- Gate: TopPagesWidgetAbstract: `admin`, `super_admin`
- Gate: TrafficChartWidgetAbstract: `admin`, `super_admin`

## Common Pitfalls

- Install LayoutBuilder first.
- Run the package setup before expecting archive/tag pages.
- Check layouts before creating article records.
- Cache and sitemap output may need regeneration after setup.

## Quick Start

1. Install the package with `composer require capell-app/blog`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin or frontend surface and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../layout-builder/README.md](../layout-builder/README.md)
- [../tags/README.md](../tags/README.md)
- [../seo-suite/README.md](../seo-suite/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
