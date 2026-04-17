# Database Reference — Capell Blog

## Migrations

| File | Effect |
| --- | --- |
| `database/migrations/create_articles_table.php` | Creates the `articles` table |
| `database/migrations/alter_tags_table.php` | Extends Spatie's `tags` and `taggables` tables with Capell workspace + site columns |

Run them via `php artisan capell:blog-install`, which also publishes the tags config override.

## `articles`

Workspace-aware page record for article content.

Key columns: `id`, `workspace_id`, `site_id`, `type_id`, `layout_id`, `name`, `meta` (JSON), publish dates (`start_date`, `end_date`), `order`, userstamps, timestamps, soft deletes.

Traits on the `Article` model: `BelongsToWorkspace`, `HasTags`, `HasTranslations`, `HasPublishDates`, `HasPageOrdering`, `HasAssets`, `HasMetaData`, `LogsActivity`, `InteractsWithMedia`, `Cloneable`, `SoftDeletes`.

## `tags` (altered)

`alter_tags_table.php` adds the following to the stock Spatie `tags` table:

- `workspace_id` — so draft tags stage inside a workspace
- `site_id` — scopes tags to a site
- `featured` — boolean flag surfaced in the admin
- `status` — publish status

The custom `Tag` model (`src/Models/Tag.php`) replaces Spatie's default. Configure it in `config/tags.php` — the installer publishes this.

## `taggables` (altered)

Adds `workspace_id` to the Spatie `taggables` pivot so tag assignments also belong to a workspace. This means attaching or detaching a tag counts as an editorial edit and does not affect live until the workspace publishes.

## Custom `Taggable` model

`src/Models/Taggable.php` — explicit Eloquent model over the pivot so it can participate in the workspace lifecycle. Morphs to the tagged model and belongs to a `Tag`.

## Factories

- `database/factories/ArticleFactory.php`
- `database/factories/ArticleTypeFactory.php`
- `database/factories/TagFactory.php`

## Relations registered at runtime

Registered in `BlogServiceProvider`:

- `Page::tags()` — morph-to-many `Capell\Blog\Models\Tag` via `taggables`
- `Site::tags()` — has-many `Capell\Blog\Models\Tag`

When the Layout package is also installed:

- `Content::tags()` — morph-to-many `Tag`
- `Tag::contents()` — morphed-by-many `Content`
