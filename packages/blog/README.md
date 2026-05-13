# Blog

Blog adds article publishing, archive pages, tag pages, article widgets, Site Discovery sitemap contributions, and frontend Livewire page components to Capell.

## At A Glance

- Package: `capell-app/blog`
- Namespace: `Capell\Blog\`
- Surfaces: Filament admin, Livewire, console, database
- Service providers: `packages/blog/src/Providers/AdminServiceProvider.php`, `packages/blog/src/Providers/BlogServiceProvider.php`, `packages/blog/src/Providers/ConsoleServiceProvider.php`, `packages/blog/src/Providers/FrontendServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/content-sections`, `capell-app/demo-kit`, `capell-app/frontend`, `capell-app/html-cache`, `capell-app/insights`, `capell-app/navigation`, `capell-app/publishing-studio`, `capell-app/site-discovery`, `capell-app/tags`

## What It Adds

Blog adds article publishing, archive pages, tag pages, article widgets, Site Discovery sitemap contributions, and frontend Livewire page components to Capell.

- Article Filament resource.
- Blog, archive, and tag frontend Livewire components.
- Article widgets and configurators for layout builder.
- Site Discovery sitemap contributions for articles, archives, and tags.
- Commands to install and create blog pages.

## Why It Matters

**For developers:** Builds on core pages, layouts, translations, page URLs, core layout builder widgets, and tags while keeping article-specific logic in actions and loaders.

**For teams:** Gives editors a dedicated article workflow that still fits the same structured publishing foundation as pages.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Insights](../insights/README.md)
- [Capell Frontend](https://github.com/capell-app/frontend)
- Core admin/frontend layout builder APIs
- [Capell Navigation](../navigation/README.md)
- [Capell Site Discovery](../site-discovery/README.md)
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

## Code Map

| Area      | Path                          | Purpose                                                             |
| --------- | ----------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/blog/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/blog/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/blog/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/blog/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/blog/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Livewire  | `packages/blog/src/Livewire`  | Interactive frontend or admin components.                           |
| Providers | `packages/blog/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/blog/resources`     | Views, translations, assets, and package resources.                 |
| Database  | `packages/blog/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/blog/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `ArticleResource`.
- Pages: `CreateArticle`, `EditArticle`, `ListArticles`.
- Widgets: `ArticleHealthWidgetAbstract`, `ArticleWidgetConfigurator`, `ListArticlesWidget`, `RelatedWidgetConfigurator`, `TopPagesWidgetAbstract`, `TrafficChartWidgetAbstract`.

## Runtime Surface

- Livewire: `Archive`, `Blog`, `Tag`.

## Commands

- `capell:blog-create-pages {site : The ID of the site to create blog pages for}` (packages/blog/src/Console/Commands/CreateBlogPagesCommand.php)
- `capell:blog-demo {--sites=} {--user=} {--limit=}` (packages/blog/src/Console/Commands/DemoCommand.php)
- `capell:blog-faker {--count=25} {--sites=} {--languages=} {--force}` (packages/blog/src/Console/Commands/FakerCommand.php)
- `capell:blog-install` (packages/blog/src/Console/Commands/InstallCommand.php)
- `capell:blog-setup {--user= : Ignored - accepted for compatibility with capell:install} {--sites= : Ignored - accepted for compatibility with capell:install} {--languages= : Ignored - accepted for compatibility with capell:install} {--url= : Ignored - accepted for compatibility with capell:install}` (packages/blog/src/Console/Commands/SetupCommand.php)
- `capell:hero-demo {--sites=}` (packages/blog/src/Console/Commands/HeroDemoCommand.php)

## Data And Persistence

- articles stores uuid, workspace, type, layout, site, meta, visible_from, and visible_until.
- Articles connect to sites, types, layouts, page URLs, translations, core layout builder widget assets, and tags.
- Blog uses the layout builder APIs provided by the admin/frontend core packages.
- Deletion and retention behaviour should be verified against the host application policy.

- Models: `Article`.
- Migrations: `2026_05_10_190842_01_create_articles_table.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Listeners: `AddBlogPagesToNavigation`, `ArticleTranslationSavedListener`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds articles table and article admin resource.
- Adds blog frontend components and Site Discovery sitemap contributions.
- Adds console commands for setup, install, demo, faker, and page creation.
- May add blog pages to navigation through listener behaviour.

## Install And Setup

- Install with `composer require capell-app/blog` in the host Capell application.
- Run the package install command above when the package needs migrations, settings, generated pages, or seed data.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- ArticleResource (packages/blog/src/Filament/Resources/Articles/ArticleResource.php, slug `article`)
- CreateArticle (packages/blog/src/Filament/Resources/Articles/Pages/CreateArticle.php)
- EditArticle (packages/blog/src/Filament/Resources/Articles/Pages/EditArticle.php)
- ListArticles (packages/blog/src/Filament/Resources/Articles/Pages/ListArticles.php)

- Gate: ArticleHealthWidgetAbstract: `developer`, `admin`, `super_admin`
- Gate: TopPagesWidgetAbstract: `admin`, `super_admin`
- Gate: TrafficChartWidgetAbstract: `admin`, `super_admin`

## Common Pitfalls

- Run the package setup before expecting archive/tag pages.
- Check layouts before creating article records.
- Cache and Site Discovery sitemap output may need regeneration after setup.

## Docs

- [blog-api.md](docs/blog-api.md)
- [blog-database.md](docs/blog-database.md)
- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [media-attachment.md](docs/media-attachment.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/blog/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Keep Blog widget setup aligned with the layout builder APIs provided by admin/frontend core packages.
- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
