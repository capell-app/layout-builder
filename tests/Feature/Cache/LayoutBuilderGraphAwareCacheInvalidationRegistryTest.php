<?php

declare(strict_types=1);

use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Models\ContentGraphEdge;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Frontend\Data\CacheInvalidationRule;
use Capell\Frontend\Support\Cache\CacheInvalidationRegistry;
use Capell\LayoutBuilder\Models\Block;

it('walks layout builder block graph dependents back to pages', function (): void {
    $block = Block::factory()->create();
    $layout = Layout::factory()->create();
    $page = Page::factory()
        ->withTranslations()
        ->create(['layout_id' => $layout->id]);

    ContentGraphEdge::query()->create([
        'source_type' => Page::class,
        'source_id' => $page->id,
        'target_type' => Layout::class,
        'target_id' => $layout->id,
        'kind' => ContentGraphEdgeKind::UsesLayout,
        'strength' => ContentGraphEdgeStrength::Strong,
        'source_package' => 'capell-app/core',
        'site_id' => $page->site_id,
    ]);

    ContentGraphEdge::query()->create([
        'source_type' => Layout::class,
        'source_id' => $layout->id,
        'target_type' => Block::class,
        'target_id' => $block->id,
        'kind' => 'uses_layout_block',
        'strength' => ContentGraphEdgeStrength::Strong,
        'source_package' => 'capell-app/layout-builder',
        'site_id' => $page->site_id,
    ]);

    $plan = resolve(CacheInvalidationRegistry::class)->planForChangedModel($block);

    expect(collect($plan->rules)->contains(
        fn (CacheInvalidationRule $rule): bool => $rule->kind === CacheInvalidationRule::KIND_PUBLIC_RENDER_DATA
            && $rule->modelType === Page::class
            && $rule->modelId === $page->id,
    ))->toBeTrue();
});
