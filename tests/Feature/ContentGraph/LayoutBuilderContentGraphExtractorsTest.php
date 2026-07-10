<?php

declare(strict_types=1);

use Capell\Core\Actions\ContentGraph\BuildContentGraphForModelAction;
use Capell\Core\Data\ContentGraph\ContentGraphEdgeData;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Enums\ContentStructure;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Support\ContentGraph\ContentGraphRegistry;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\LayoutWidgetContentGraphExtractor;
use Capell\LayoutBuilder\Support\ContentGraph\Extractors\PageWidgetExtensionContentGraphExtractor;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\RecordingDependencyResolver;
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

it('uses the registered page and layout extension extractors and deduplicates their edges', function (): void {
    $language = Language::factory()->createOne(['code' => 'en']);
    $site = Site::factory()->createOne(['language_id' => $language->id]);
    $mediaOwner = Page::factory()->site($site)->create();
    $media = Media::factory()->model($mediaOwner)->create();
    $linkedPage = Page::factory()->site($site)->create();
    RecordingDependencyResolver::$identifiers = [
        'media:' . $media->getKey(),
        'media:' . $media->getKey(),
        'content:page:' . $linkedPage->getKey(),
    ];
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        dependencyResolver: RecordingDependencyResolver::class,
    ));

    $block = layoutBuilderExtensionContentGraphBlock('graph-instance');
    $page = Page::factory()
        ->site($site)
        ->state(['content_structure_override' => ContentStructure::Blocks->value])
        ->withTranslations($language, ['title' => 'Graph', 'content' => [$block]], contentStructure: ContentStructure::Blocks)
        ->create();
    $layout = Layout::factory()->site($site)->create([
        'containers' => ['main' => ['widgets' => [$block]]],
    ]);

    $registry = resolve(ContentGraphRegistry::class);
    expect(collect($registry->forModel(Page::class))->contains(
        fn (object $extractor): bool => $extractor instanceof PageWidgetExtensionContentGraphExtractor,
    ))->toBeTrue()
        ->and(collect($registry->forModel(Layout::class))->contains(
            fn (object $extractor): bool => $extractor instanceof LayoutWidgetContentGraphExtractor,
        ))->toBeTrue();

    $pageEdges = BuildContentGraphForModelAction::run($page)->edges;
    $layoutEdges = BuildContentGraphForModelAction::run($layout)->edges;

    foreach ([$pageEdges, $layoutEdges] as $edges) {
        expect(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::UsesMedia, Media::class, (int) $media->getKey(), ContentGraphEdgeStrength::Strong))->toBeTrue()
            ->and(layoutBuilderContentGraphHasEdge($edges, 'uses_content', Page::class, (int) $linkedPage->getKey(), ContentGraphEdgeStrength::Strong))->toBeTrue()
            ->and(collect($edges)->filter(fn (ContentGraphEdgeData $edge): bool => $edge->target->modelType === Media::class && $edge->target->modelId === (int) $media->getKey()))->toHaveCount(1);
    }
});

/** @return array<string, mixed> */
function layoutBuilderExtensionContentGraphBlock(string $instanceId): array
{
    return [
        'type' => 'capell-app.slideshow',
        'data' => [
            'title' => 'Graph widget',
            '__capell' => ['instance_id' => $instanceId, 'state_version' => 2],
        ],
    ];
}

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
