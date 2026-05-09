<?php

declare(strict_types=1);

use Capell\Core\Actions\ContentGraph\BuildContentGraphForModelAction;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Models\Page;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;

it('extracts page dependencies from nested navigation items', function (): void {
    $page = Page::factory()->create();
    $childPage = Page::factory()->create();
    $navigation = Navigation::factory()->create([
        'site_id' => $page->site_id,
        'items' => [
            [
                'type' => NavigationItemType::Page->value,
                'label' => 'Parent',
                'data' => [
                    'pageable_type' => $page->getMorphClass(),
                    'pageable_id' => $page->id,
                ],
                'children' => [
                    [
                        'type' => NavigationItemType::Page->value,
                        'label' => 'Child',
                        'data' => [
                            'pageable_type' => $childPage->getMorphClass(),
                            'pageable_id' => $childPage->id,
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ],
    ]);

    $edges = collect(BuildContentGraphForModelAction::run($navigation)->edges);

    expect($edges)->toHaveCount(2)
        ->and($edges->pluck('kind')->unique()->all())->toBe([ContentGraphEdgeKind::LinksToPage])
        ->and($edges->pluck('strength')->unique()->all())->toBe([ContentGraphEdgeStrength::Strong])
        ->and($edges->pluck('siteId')->unique()->all())->toBe([(int) $navigation->site_id])
        ->and($edges->pluck('target.modelId')->sort()->values()->all())->toBe([
            $page->id,
            $childPage->id,
        ]);
});
