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
use Capell\LayoutBuilder\Models\Element;
use Capell\LayoutBuilder\Models\ElementAsset;
use Illuminate\Database\Eloquent\Model;

it('extracts layout element content graph dependencies', function (): void {
    $element = Element::factory()->create();
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'elements' => [
                    ['element_key' => $element->key],
                ],
            ],
        ],
    ]);

    $edges = BuildContentGraphForModelAction::run($layout)->edges;

    expect(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::UsesElement, Element::class, $element->id, ContentGraphEdgeStrength::Strong))->toBeTrue();
});

it('extracts element media and default asset dependencies', function (): void {
    $element = Element::factory()->create();
    $image = Media::factory()->model($element)->collection(MediaCollectionEnum::Image)->create();
    $backgroundImage = Media::factory()->model($element)->collection(MediaCollectionEnum::BackgroundImage)->create();
    $elementAsset = ElementAsset::factory()->widget($element)->create();

    $edges = BuildContentGraphForModelAction::run($element)->edges;

    expect(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::UsesMedia, Media::class, $image->id, ContentGraphEdgeStrength::Strong))->toBeTrue()
        ->and(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::UsesMedia, Media::class, $backgroundImage->id, ContentGraphEdgeStrength::Strong))->toBeTrue()
        ->and(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::UsesElement, ElementAsset::class, $elementAsset->id, ContentGraphEdgeStrength::Informational))->toBeTrue();
});

it('extracts element asset page links from pageable asset and linked page references', function (): void {
    $element = Element::factory()->create();
    $pageablePage = Page::factory()->create();
    $assetPage = Page::factory()->create();
    $linkedPage = Page::factory()->create();
    $elementAsset = ElementAsset::factory()
        ->widget($element)
        ->page($pageablePage)
        ->asset($assetPage)
        ->create([
            'meta' => [
                'linked_pageable_type' => $linkedPage->getMorphClass(),
                'linked_pageable_id' => $linkedPage->getKey(),
            ],
        ]);

    $edges = BuildContentGraphForModelAction::run($elementAsset)->edges;

    expect(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::LinksToPage, Page::class, $pageablePage->id, ContentGraphEdgeStrength::Strong))->toBeTrue()
        ->and(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::LinksToPage, Page::class, $assetPage->id, ContentGraphEdgeStrength::Strong))->toBeTrue()
        ->and(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::LinksToPage, Page::class, $linkedPage->id, ContentGraphEdgeStrength::Strong))->toBeTrue();
});

it('extracts element asset element and media asset dependencies', function (): void {
    $element = Element::factory()->create();
    $mediaOwner = Page::factory()->create();
    $media = Media::factory()->model($mediaOwner)->create();
    $elementAsset = ElementAsset::factory()
        ->widget($element)
        ->create([
            'asset_type' => $media->getMorphClass(),
            'asset_id' => (string) $media->getKey(),
        ]);

    $edges = BuildContentGraphForModelAction::run($elementAsset)->edges;

    expect(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::UsesElement, Element::class, $element->id, ContentGraphEdgeStrength::Strong))->toBeTrue()
        ->and(layoutBuilderContentGraphHasEdge($edges, ContentGraphEdgeKind::UsesMedia, Media::class, $media->id, ContentGraphEdgeStrength::Strong))->toBeTrue();
});

it('uses layout element id for element asset relationships', function (): void {
    $element = Element::factory()->create();

    ElementAsset::factory()
        ->widget($element)
        ->create();

    expect($element->assets()->getQualifiedForeignKeyName())->toBe('layout_element_assets.layout_element_id')
        ->and($element->assets()->exists())->toBeTrue();
});

/**
 * @param  array<int, ContentGraphEdgeData>  $edges
 * @param  class-string<Model>  $targetType
 */
function layoutBuilderContentGraphHasEdge(
    array $edges,
    ContentGraphEdgeKind $kind,
    string $targetType,
    int $targetId,
    ContentGraphEdgeStrength $strength,
): bool {
    return collect($edges)->contains(
        fn (ContentGraphEdgeData $edge): bool => $edge->kind === $kind
            && $edge->target->modelType === $targetType
            && $edge->target->modelId === $targetId
            && $edge->strength === $strength,
    );
}
