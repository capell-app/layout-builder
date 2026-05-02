---
name: blog
description: Use when working on the Capell Blog package. Covers Article model (Pageable, not a standalone entity), CreateBlogPagesAction setup flow, BlogPageTypeEnum/BlogLayoutEnum, tags integration, archive/tag page structure, ArticleResource Filament UI, observer pattern, and workspace/draft integration.
---

# Capell Blog

Blog provides article publishing tightly integrated with Capell's page/type/layout system.

---

## Key Concept: Articles Are Pages

Articles implement `Pageable` — they're routed, SEO'd, and cached like any Capell page. They do **not** have a parent-child hierarchy (`hasPageHierarchy() => false`). Instead they associate with multiple blog/archive pages via `MorphToMany`.

This means:

- Articles inherit full Capell SEO, versioning, and multilingual support
- Article URLs are managed via `PageUrl` like all other pages
- Publishing flows through the `Publishable` trait (same as pages)
- Do not build a separate routing layer — use Capell's page routing

---

## Key Model: Article

- Key fields: `name`, `type_id`, `status`, `publish_at`, `publish_status`, `language_id`, `site_id`, `workspace_id`
- Relationships:
    - `pages()` — MorphToMany (article appears on multiple blog/archive pages)
    - Tags via `HasTags` (Capell Tags package)
- Traits: `HasCapellMedia`, `HasMetaData`, `HasPublishDates`, `HasStatus`, `HasTranslations`, `HasUserstamps`, `CloneableExcept`, `LogsActivity`, `HasTags`, `BelongsToWorkspace`
- Observer: `ArticleObserver` — handles cache invalidation and content sync on create/update/delete

**Do not put cache invalidation or sync logic inline in actions.** Add it to `ArticleObserver` instead. Translation-specific side effects go in `ArticleTranslationSavedListener`.

---

## Blog Page Structure

A full blog install creates a set of interconnected pages. `CreateBlogPagesAction` sets all of this up:

```
Site
└── Blog root page (BlogPageTypeEnum::Blog)
    ├── Archives page (layout: Archives)
    │   └── Archive detail pages (layout: Results)
    └── Tag pages (one per tag)
```

Never recreate this structure manually — use `CreateBlogPagesAction` or `php artisan blog:setup`.

### BlogPageTypeEnum

Fixed page types for the blog structure:

| Case      | Purpose                |
| --------- | ---------------------- |
| `Blog`    | Root blog listing page |
| `Archive` | Date-based archive     |
| `Article` | Individual article     |

### BlogLayoutEnum

| Case       | Purpose                         |
| ---------- | ------------------------------- |
| `Archives` | Archive list display            |
| `Results`  | Archive detail / result display |

---

## Key Actions

| Action                   | Purpose                                                                               |
| ------------------------ | ------------------------------------------------------------------------------------- |
| `CreateBlogPagesAction`  | Full blog structure install — root page, archives, tag pages, navigation registration |
| `GetArticleLayoutAction` | Resolves which layout renders a given article                                         |
| `GenerateArchiveUrl`     | Creates archive URLs (monthly/yearly)                                                 |
| `InstallPackageAction`   | Package installation setup                                                            |

---

## Filament Resource: ArticleResource

- **Form:** tabbed via `ArticleForm` — content, tags, SEO, publishing
- **Tables:** `ArticlesTable` (list view), `ArticlePagesTable` (which pages each article appears on)
- **Widgets:** `ArticleHealthWidget` (content quality), `TopPagesWidget`, `TrafficChartWidget`

---

## Tags Integration

Articles use the Capell `Tags` package via `HasTags`. Tag pages are auto-created during blog install.

```php
// Filter articles by tag
Article::withTag('my-tag')->get();
```

Tags are managed through the Tags package admin — do not store tags as a raw JSON column.

---

## Workspace Integration

Articles have `workspace_id` and use `BelongsToWorkspace`. Draft articles live in a workspace and publish through the Workspaces approval pipeline — the same flow as pages.

---

## Commands

| Command                                | Purpose                            |
| -------------------------------------- | ---------------------------------- |
| `php artisan blog:install`             | Package installation               |
| `php artisan blog:setup`               | Post-install configuration         |
| `php artisan capell:blog:create-pages` | Create default blog page structure |
| `php artisan capell:blog:demo`         | Seed demo articles                 |

---

## Testing Blog

Test actions directly with real data — articles are Pageable and rely on site/language config:

```php
it('creates an article', function () {
    $site = Site::factory()->create();

    $article = CreateArticleAction::run(ArticleData::from([
        'name' => 'My Article',
        'site_id' => $site->id,
    ]));

    expect($article)->name->toBe('My Article');
});
```
