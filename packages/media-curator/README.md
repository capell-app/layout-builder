# Capell Media Curator

**Product group:** Capell Foundation
**Tier:** Free

Media Curator swaps Capell's default Spatie MediaLibrary backend for [Awcodes Curator](https://github.com/awcodes/filament-curator). Use it when your editors prefer Curator's media library and your models only need one asset per media slot.

## When to install it

Install Media Curator when a project wants a Curator-backed picker for single-image fields such as hero image, thumbnail, social image, or logo.

Stay on the default Spatie backend if you need galleries, ordered media collections, responsive image sets, or Spatie conversions.

## Quick install

```bash
composer require capell-app/media-curator
php artisan migrate
php artisan optimize:clear
```

The package auto-registers through Laravel discovery and rebinds Capell's media contracts.

## What appears in the admin

| Area         | What editors can do                                 |
| ------------ | --------------------------------------------------- |
| Media fields | Pick Curator media through `CuratorPicker`          |
| Owner forms  | Store one selected media record per configured slot |

## What developers get

- `CuratorMedia` implementing Capell's `MediaContract`.
- `CuratorMediaFieldFactory` returning Curator picker fields.
- `InteractsWithCuratorMedia` for owner models.
- A migration command for moving existing Spatie media rows into Curator records.

## Model setup

Add one nullable foreign key for each media slot:

```php
Schema::table('pages', function (Blueprint $table): void {
    $table->foreignId('image_id')->nullable()->constrained('curator')->nullOnDelete();
    $table->foreignId('social_image_id')->nullable()->constrained('curator')->nullOnDelete();
});
```

Then use the Curator trait on the owner model:

```php
use Capell\Core\Contracts\Media\HasMediaContract;
use Capell\MediaCurator\Concerns\InteractsWithCuratorMedia;

final class Page extends Model implements HasMediaContract
{
    use InteractsWithCuratorMedia;
}
```

## Migrate existing Spatie media

```bash
php artisan capell:media-migrate-to-curator --dry-run
php artisan capell:media-migrate-to-curator
```

Run the dry run first. Add missing foreign-key columns before the real migration.
