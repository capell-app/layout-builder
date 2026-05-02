<?php

declare(strict_types=1);

use Capell\Core\Enums\TranslatableType;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Workspaces\Actions\Dashboard\BuildContentHealthAction;
use Capell\Workspaces\Models\Workspace;

it('counts pages with missing meta description', function (): void {
    $workspace = Workspace::factory()->published()->create();

    // 3 pages with empty/missing meta description
    $missingPages = Page::factory()->count(3)->create(['workspace_id' => $workspace->id]);
    foreach ($missingPages as $page) {
        Translation::factory()->create([
            'translatable_type' => TranslatableType::Page->value,
            'translatable_id' => $page->id,
            'meta' => ['description' => ''],
        ]);
    }

    // 2 pages with populated meta description
    $populatedPages = Page::factory()->count(2)->create(['workspace_id' => $workspace->id]);
    foreach ($populatedPages as $page) {
        Translation::factory()->create([
            'translatable_type' => TranslatableType::Page->value,
            'translatable_id' => $page->id,
            'meta' => ['description' => 'Some description'],
        ]);
    }

    $result = BuildContentHealthAction::run();
    $issue = $result->issues->toCollection()->firstWhere('id', 'missing_meta');

    expect($issue->count)->toBe(3);
});

it('counts all pages sharing a duplicate title within the same site', function (): void {
    $siteA = Site::factory()->withTranslations()->create();
    $siteB = Site::factory()->withTranslations()->create();

    $workspace = Workspace::factory()->published()->create();

    // 3 pages titled "About" in site A — all 3 are duplicates
    $pagesA = Page::factory()->count(3)->site($siteA)->create(['workspace_id' => $workspace->id]);
    foreach ($pagesA as $page) {
        Translation::factory()->create([
            'translatable_type' => TranslatableType::Page->value,
            'translatable_id' => $page->id,
            'title' => 'About',
        ]);
    }

    // 1 page titled "About" in site B — unique within that site, not a duplicate
    $pageB = Page::factory()->site($siteB)->create(['workspace_id' => $workspace->id]);
    Translation::factory()->create([
        'translatable_type' => TranslatableType::Page->value,
        'translatable_id' => $pageB->id,
        'title' => 'About',
    ]);

    $result = BuildContentHealthAction::run();
    $issue = $result->issues->toCollection()->firstWhere('id', 'duplicate_titles');

    // Only the 3 site A pages are in a duplicate group
    expect($issue->count)->toBe(3);
});

it('only counts duplicates within the same site', function (): void {
    $siteA = Site::factory()->withTranslations()->create();
    $siteB = Site::factory()->withTranslations()->create();

    $workspace = Workspace::factory()->published()->create();

    // 1 page "About" in each site — not duplicates of each other
    $pageA = Page::factory()->site($siteA)->create(['workspace_id' => $workspace->id]);
    Translation::factory()->create([
        'translatable_type' => TranslatableType::Page->value,
        'translatable_id' => $pageA->id,
        'title' => 'About',
    ]);

    $pageB = Page::factory()->site($siteB)->create(['workspace_id' => $workspace->id]);
    Translation::factory()->create([
        'translatable_type' => TranslatableType::Page->value,
        'translatable_id' => $pageB->id,
        'title' => 'About',
    ]);

    $result = BuildContentHealthAction::run();
    $issue = $result->issues->toCollection()->firstWhere('id', 'duplicate_titles');

    expect($issue->count)->toBe(0);
});

it('counts stale pages older than the threshold', function (): void {
    $workspace = Workspace::factory()->published()->create();

    // 2 pages updated 100 days ago — stale
    Page::factory()->count(2)->create([
        'workspace_id' => $workspace->id,
        'updated_at' => now()->subDays(100),
    ]);

    // 1 page updated 30 days ago — not stale
    Page::factory()->create([
        'workspace_id' => $workspace->id,
        'updated_at' => now()->subDays(30),
    ]);

    $result = BuildContentHealthAction::run(staleDays: 90);
    $issue = $result->issues->toCollection()->firstWhere('id', 'stale');

    expect($issue->count)->toBe(2);
});

it('excludes draft workspace pages from every count', function (): void {
    $draft = Workspace::factory()->open()->create();

    $draftPage = Page::factory()->create(['workspace_id' => $draft->id]);
    Translation::factory()->create([
        'translatable_type' => TranslatableType::Page->value,
        'translatable_id' => $draftPage->id,
        'meta' => ['description' => ''],
    ]);

    $result = BuildContentHealthAction::run();
    $missingMeta = $result->issues->toCollection()->firstWhere('id', 'missing_meta');
    $stale = $result->issues->toCollection()->firstWhere('id', 'stale');

    expect($missingMeta->count)->toBe(0)
        ->and($stale->count)->toBe(0);
});

it('filters by site when provided', function (): void {
    $siteA = Site::factory()->withTranslations()->create();
    $siteB = Site::factory()->withTranslations()->create();

    $workspace = Workspace::factory()->published()->create();

    // Page in site A with missing meta
    $pageA = Page::factory()->site($siteA)->create([
        'workspace_id' => $workspace->id,
        'updated_at' => now()->subDays(100),
    ]);
    Translation::factory()->create([
        'translatable_type' => TranslatableType::Page->value,
        'translatable_id' => $pageA->id,
        'meta' => ['description' => ''],
    ]);

    // Page in site B with missing meta
    $pageB = Page::factory()->site($siteB)->create([
        'workspace_id' => $workspace->id,
        'updated_at' => now()->subDays(100),
    ]);
    Translation::factory()->create([
        'translatable_type' => TranslatableType::Page->value,
        'translatable_id' => $pageB->id,
        'meta' => ['description' => ''],
    ]);

    $result = BuildContentHealthAction::run(site: $siteA);
    $missingMeta = $result->issues->toCollection()->firstWhere('id', 'missing_meta');
    $stale = $result->issues->toCollection()->firstWhere('id', 'stale');

    expect($missingMeta->count)->toBe(1)
        ->and($stale->count)->toBe(1);
});

it('skips empty_content check when no language is given', function (): void {
    $workspace = Workspace::factory()->published()->create();
    $language = Language::factory()->create();

    $page = Page::factory()->create(['workspace_id' => $workspace->id]);
    Translation::factory()->create([
        'translatable_type' => TranslatableType::Page->value,
        'translatable_id' => $page->id,
        'language_id' => $language->id,
        'content' => null,
    ]);

    // No language passed — empty_content should always be 0
    $result = BuildContentHealthAction::run();
    $issue = $result->issues->toCollection()->firstWhere('id', 'empty_content');

    expect($issue->count)->toBe(0);
});
