<?php

declare(strict_types=1);

use Capell\Core\Actions\ContentGraph\BuildContentGraphForModelAction;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeKind;
use Capell\Core\Enums\ContentGraph\ContentGraphEdgeStrength;
use Capell\Core\Models\Page;
use Capell\SeoSuite\Models\BrokenLink;
use Capell\SeoSuite\Models\PageSeoSnapshot;

it('extracts weak page seo snapshot dependencies', function (): void {
    $page = Page::factory()->create();
    $snapshot = PageSeoSnapshot::query()->create([
        'page_id' => $page->id,
        'site_id' => $page->site_id,
        'language_id' => $page->site->language_id,
        'score' => 80,
        'critical_count' => 0,
        'warning_count' => 1,
        'issue_keys' => [],
        'passed_check_keys' => [],
        'computed_at' => now(),
    ]);

    $edges = collect(BuildContentGraphForModelAction::run($snapshot)->edges);

    expect($edges)->toHaveCount(1)
        ->and($edges->first()?->kind)->toBe(ContentGraphEdgeKind::DescribesPage)
        ->and($edges->first()?->strength)->toBe(ContentGraphEdgeStrength::Weak)
        ->and($edges->first()?->target->modelId)->toBe($page->id);
});

it('extracts weak broken link page dependencies', function (): void {
    $page = Page::factory()->create();
    $brokenLink = BrokenLink::query()->create([
        'page_id' => $page->id,
        'target_url' => 'https://example.com/missing',
        'http_status' => 404,
        'last_checked_at' => now(),
    ]);

    $edges = collect(BuildContentGraphForModelAction::run($brokenLink)->edges);

    expect($edges)->toHaveCount(1)
        ->and($edges->first()?->kind)->toBe(ContentGraphEdgeKind::FoundOnPage)
        ->and($edges->first()?->strength)->toBe(ContentGraphEdgeStrength::Weak)
        ->and($edges->first()?->target->modelId)->toBe($page->id);
});
