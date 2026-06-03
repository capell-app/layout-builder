<?php

declare(strict_types=1);

use Capell\Core\Actions\ContentGraph\BuildContentGraphForModelAction;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeData;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;

const LAYOUT_BUILDER_USES_LAYOUT_BLOCK = 'uses_layout_widget';

it('extracts layout widget content graph dependencies', function (): void {
    $widget = Widget::factory()->create();
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $widget->key],
                ],
            ],
        ],
    ]);

    $edges = BuildContentGraphForModelAction::run($layout)->edges;

    expect(layoutBuilderContentGraphHasEdge($edges, LAYOUT_BUILDER_USES_LAYOUT_BLOCK, Widget::class, $widget->id, ContentGraphEdgeStrength::Strong))->toBeTrue();
});

it('extracts widget media and default asset dependencies', function (): void {
    $widget = Widget::factory()->create();
    $image = Media::factory()->model($widget)->collection(MediaCollectionEnum::Image)->create();
    $backgroundImage = Media::factory()->model($widget)->collection(MediaCollectionEnum::BackgroundImage)->create();
    $widgetAsset = WidgetAsset::factory()->widget($widget)->create();

    $edges = BuildContentGraphForModelAction::run($widget)->edges;

    expect(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::UsesMedia, Media::class, $image->id, ContentGraphEdgeStrength::Strong))->toBeTrue()
        ->and(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::UsesMedia, Media::class, $backgroundImage->id, ContentGraphEdgeStrength::Strong))->toBeTrue()
        ->and(layoutBuilderContentGraphHasEdge($edges, LAYOUT_BUILDER_USES_LAYOUT_BLOCK, WidgetAsset::class, $widgetAsset->id, ContentGraphEdgeStrength::Informational))->toBeTrue();
});

it('extracts widget asset page links from pageable asset and linked page references', function (): void {
    $widget = Widget::factory()->create();
    $pageablePage = Page::factory()->create();
    $assetPage = Page::factory()->create();
    $linkedPage = Page::factory()->create();
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->page($pageablePage)
        ->asset($assetPage)
        ->create([
            'meta' => [
                'linked_pageable_type' => $linkedPage->getMorphClass(),
                'linked_pageable_id' => $linkedPage->getKey(),
            ],
        ]);

    $edges = BuildContentGraphForModelAction::run($widgetAsset)->edges;

    expect(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::LinksToPage, Page::class, $pageablePage->id, ContentGraphEdgeStrength::Strong))->toBeTrue()
        ->and(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::LinksToPage, Page::class, $assetPage->id, ContentGraphEdgeStrength::Strong))->toBeTrue()
        ->and(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::LinksToPage, Page::class, $linkedPage->id, ContentGraphEdgeStrength::Strong))->toBeTrue();
});

it('extracts widget asset widget and media asset dependencies', function (): void {
    $widget = Widget::factory()->create();
    $mediaOwner = Page::factory()->create();
    $media = Media::factory()->model($mediaOwner)->create();
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->create([
            'asset_type' => $media->getMorphClass(),
            'asset_id' => (string) $media->getKey(),
        ]);

    $edges = BuildContentGraphForModelAction::run($widgetAsset)->edges;

    expect(layoutBuilderContentGraphHasEdge($edges, LAYOUT_BUILDER_USES_LAYOUT_BLOCK, Widget::class, $widget->id, ContentGraphEdgeStrength::Strong))->toBeTrue()
        ->and(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::UsesMedia, Media::class, $media->id, ContentGraphEdgeStrength::Strong))->toBeTrue();
});

it('uses layout widget id for widget asset relationships', function (): void {
    $widget = Widget::factory()->create();

    WidgetAsset::factory()
        ->widget($widget)
        ->create();

    expect($widget->assets()->getQualifiedForeignKeyName())->toBe('widget_assets.widget_id')
        ->and($widget->assets()->exists())->toBeTrue();
});

/**
 * @param  array<int, ContentGraphEdgeData>  $edges
 * @param  class-string<Model>  $targetType
 */
function layoutBuilderContentGraphHasEdge(
    array $edges,
    ContentGraphEdgeKind|string $kind,
    string $targetType,
    int $targetId,
    ContentGraphEdgeStrength $strength,
): bool {
    return collect($edges)->contains(
        fn (ContentGraphEdgeData $edge): bool => (is_string($edge->kind) ? $edge->kind : $edge->kind->value) === (is_string($kind) ? $kind : $kind->value)
            && $edge->target->modelType === $targetType
            && $edge->target->modelId === $targetId
            && $edge->strength === $strength,
    );
}
