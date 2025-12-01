<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\MediaFactory;
use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Layout\Database\Factories\ContentTypeFactory;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\Widget;
use Capell\Layout\Models\WidgetAsset;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

it('belongs to a site', function (): void {
    $site = Site::factory()->create();
    $content = Content::factory()->create(['site_id' => $site->id]);

    expect($content->site)->toBeInstanceOf(Site::class)
        ->and($content->site->id)->toBe($site->id);
});

it('belongs to a type', function (): void {
    $type = (new ContentTypeFactory)->create();
    $content = Content::factory()->create(['type_id' => $type->id]);

    expect($content->type)->toBeInstanceOf(Type::class)
        ->and($content->type->id)->toBe($type->id);
});

it('belongs to an image', function (): void {
    $content = Content::factory()->create();

    $media = MediaFactory::new([
        'model_type' => resolve(Content::class)->getMorphClass(),
        'model_id' => $content->id,
    ])
        ->create();

    expect($content->image)->toBeInstanceOf(Media::class)
        ->and($content->image->id)->toBe($media->id);
});

it('has many translations', function (): void {
    $content = Content::factory()->create();
    $translation = Translation::factory()->create(['translatable_id' => $content->id, 'translatable_type' => 'content']);

    expect($content->translations)
        ->toHaveCount(1)
        ->and($content->translations->pluck('id'))
        ->toContain($translation->id);
});

it('has many assets', function (): void {
    $content = Content::factory()->create();
    $resource = AssetRelation::factory()->related($content)->create();

    expect($content->assets->pluck('id'))->toContain($resource->id);
});

it('has many widgets', function (): void {
    $content = Content::factory()->create();
    $widget = Widget::factory()->create();
    WidgetAsset::factory()->create(['asset_id' => $content->id, 'asset_type' => 'content', 'widget_id' => $widget->id]);

    expect($content->widgets->pluck('widget_id'))->toContain($widget->id);
});

it('has many pages', function (): void {
    $content = Content::factory()->create();
    $page = Page::factory()->create();
    $widgetAsset = WidgetAsset::factory()->create(['asset_id' => $content->id, 'asset_type' => 'content', 'page_id' => $page->id]);

    expect($content->pages)
        ->toHaveCount(1)
        ->and($content->pages->first()->page_id)->toBe($page->id);
});
