# Database Reference - Capell Blog

The Blog package integrates with tagging and provides an additional migration, factories, and runtime relations.

## Migrations

- `database/migrations/create_articles_table.php`
- `database/migrations/alter_tags_table.php`

Run via the installer:

```
php artisan capell-blog:install
```

This publishes the migration and runs `php artisan migrate`.

The package also uses the polymorphic `taggables` pivot table for tagging.

## Factories

- `database/factories/ArticleFactory.php`
- `database/factories/ArticleTypeFactory.php`
- `database/factories/TagFactory.php`

## Relations registered at runtime

See `src/Providers/BlogServiceProvider.php` for relation registration:

- `Page::tags()` — morph-to-many `Capell\\Blog\\Models\\Tag` via `taggables`
- `Site::tags()` — has-many `Capell\\Blog\\Models\\Tag`
- If Layout is present:
    - `Content::tags()` — morph-to-many `Tag`
    - `Tag::contents()` — morphed-by-many `Content`
