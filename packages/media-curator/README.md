# Capell Media Curator

Awcodes Curator backend for Capell CMS media. Drop-in replacement for the default Spatie MediaLibrary backend via Capell's media contracts.

## What it does

Swaps the two media contracts shipped in `capell-app/core`:

- `config('capell.media.model')` → `Capell\MediaCurator\Models\CuratorMedia`
- `Capell\Core\Contracts\Media\MediaFieldFactory` → `Capell\MediaCurator\Filament\Components\CuratorMediaFieldFactory`

`CuratorMedia` extends `Awcodes\Curator\Models\Media` (table `curator`) and implements `MediaContract`. The Filament factory returns a `CuratorPicker` field.

## Install

```bash
composer require capell-app/media-curator
```

Auto-registers via Laravel package discovery. Run Curator's own migrations to create the `curator` table.

## Limitations (read before switching)

- **One media per collection.** This backend is single-FK: each collection maps to ONE `{snake_collection}_id` column on the owner table. There are no galleries, no ordered multi-item collections.
- **No responsive image sets.** `hasResponsiveImages()` returns `false` and `getSrcset()` returns `''`.
- **No Spatie-style conversions.** `hasConversion()` is always `false`; the `$conversion` argument on `getUrl()` / `getFullUrl()` is accepted but ignored.

If a consumer model needs multi-item collections or Spatie conversions, keep it on the default backend.

## Model setup

Replace `use HasCapellMedia` with `use InteractsWithCuratorMedia` on any owner model, and add one FK column per media collection it uses. Column name is `Str::snake($collection) . '_id'`:

| Collection    | FK column         |
| ------------- | ----------------- |
| `image`       | `image_id`        |
| `socialImage` | `social_image_id` |
| `hero`        | `hero_id`         |

Example migration:

```php
Schema::table('pages', function (Blueprint $table): void {
    $table->foreignId('image_id')->nullable()->constrained('curator')->nullOnDelete();
    $table->foreignId('social_image_id')->nullable()->constrained('curator')->nullOnDelete();
});
```

Example model:

```php
use Capell\Core\Contracts\Media\HasMediaContract;
use Capell\MediaCurator\Concerns\InteractsWithCuratorMedia;

final class Page extends Model implements HasMediaContract
{
    use InteractsWithCuratorMedia;
}
```

## Migrating existing Spatie data

The Phase 4 Artisan command migrates existing `media` rows into the `curator` table and populates the new FK columns:

```bash
php artisan capell:media-migrate-to-curator
```

Run it once per environment during the cutover.
