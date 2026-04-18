<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\MediaFactory;
use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Mosaic\Database\Factories\ContentTypeFactory;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('belongs to a site', function (): void {
    $site = Site::factory()->create();
    $content = Collection::factory()->site($site)->create();

    expect($content->site)->toBeInstanceOf(Site::class)
        ->and($content->site->id)->toBe($site->id);
});

it('belongs to a type', function (): void {
    $type = (new ContentTypeFactory)->create();
    $content = Collection::factory()->type($type)->create();

    expect($content->type)->toBeInstanceOf(Type::class)
        ->and($content->type->id)->toBe($type->id);
});

it('belongs to an image', function (): void {
    $content = Collection::factory()->create();
    $media = MediaFactory::new()->model($content)->create();

    expect($content->image)->toBeInstanceOf(Media::class)
        ->and($content->image->id)->toBe($media->id);
});

it('has many translations', function (): void {
    $content = Collection::factory()->create();
    $translation = Translation::factory()->translatable($content)->create();

    expect($content->translations)
        ->toHaveCount(1)
        ->and($content->translations->pluck('id'))
        ->toContain($translation->id);
});

it('has many assets', function (): void {
    $content = Collection::factory()->create();
    $resource = AssetRelation::factory()->related($content)->create();

    expect($content->assets->pluck('id'))->toContain($resource->id);
});

it('has many widgets', function (): void {
    $content = Collection::factory()->create();
    $widget = Widget::factory()->create();
    WidgetAsset::factory()->asset($content)->widget($widget)->create();

    expect($content->widgets->pluck('widget_id'))->toContain($widget->id);
});

it('has many pages', function (): void {
    $content = Collection::factory()->create();
    $page = Page::factory()->create();
    WidgetAsset::factory()->asset($content)->page($page)->create();

    expect($content->pages)
        ->toHaveCount(1)
        ->and($content->pages->first())
        ->pageable_type->toBe($page->getMorphClass())
        ->pageable_id->toBe($page->id);
});

it('creates a content with parent', function (): void {
    $parent = Collection::factory()->create();
    $content = Collection::factory()->parent($parent)->create();

    expect($content)
        ->parent_id->toBe($parent->id)
        ->parent->toBeInstanceOf(Collection::class);
});
