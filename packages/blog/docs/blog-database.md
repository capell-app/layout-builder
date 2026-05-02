# Database Reference — Capell Blog

## Migrations

| File                                            | Effect                       |
| ----------------------------------------------- | ---------------------------- |
| `database/migrations/create_articles_table.php` | Creates the `articles` table |

Run them via `php artisan capell:blog-install`, which also publishes the tags config override.

## `articles`

Workspace-aware page record for article content.

Key columns: `id`, `workspace_id`, `site_id`, `type_id`, `layout_id`, `name`, `meta` (JSON), publish dates (`start_date`, `end_date`), `order`, userstamps, timestamps, soft deletes.

Traits on the `Article` model: `BelongsToWorkspace`, `HasTags`, `HasTranslations`, `HasPublishDates`, `HasPageOrdering`, `HasAssets`, `HasMetaData`, `LogsActivity`, `InteractsWithMedia`, `Cloneable`, `SoftDeletes`.

## Factories

- `database/factories/ArticleFactory.php`
- `database/factories/ArticleTypeFactory.php`

## Relations registered at runtime

Registered in `BlogServiceProvider`:

- `Page::tags()` — morph-to-many `Capell\Blog\Models\Tag` via `taggables`
- `Site::tags()` — has-many `Capell\Blog\Models\Tag`

When the Layout package is also installed:

- `Content::tags()` — morph-to-many `Tag`
- `Tag::contents()` — morphed-by-many `Content`
