<?php

declare(strict_types=1);

use Capell\Core\EventSourcing\Rollback\Actions\ApplyRollbackAction;
use Capell\Core\EventSourcing\Rollback\Actions\BuildRollbackPreviewAction;
use Capell\Core\EventSourcing\Rollback\Actions\CompareWithPreviousVersionAction;
use Capell\Core\EventSourcing\Rollback\RollbackFieldChangeData;
use Capell\Core\EventSourcing\Rollback\RollbackService;
use Capell\Core\EventSourcing\Serializers\PageStateSerializer;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;

function layoutRollbackPage(): Page
{
    $page = Page::factory()->create();
    Translation::factory()->translatable($page)->language(Language::factory()->create())
        ->create(['title' => 'Title', 'content' => '<p>content</p>']);

    return $page;
}

function recordLayoutRevision(Page $page): int
{
    $page->load(['translations', 'pageUrls']);
    $page->save();

    return resolve(RollbackService::class)->currentVersion((string) $page->uuid);
}

/**
 * @return array<int, array{widget_id: int|null, container: string|null, order: int, meta: mixed}>
 */
function pageLayout(Page $page): array
{
    return WidgetAsset::query()
        ->where('pageable_type', $page->getMorphClass())
        ->where('pageable_id', $page->getKey())
        ->orderBy('container')
        ->orderBy('order')
        ->get()
        ->map(static fn (WidgetAsset $asset): array => [
            'widget_id' => $asset->widget_id,
            'container' => $asset->container,
            'order' => $asset->order,
            'meta' => $asset->meta,
        ])
        ->all();
}

it('restores the page layout widgets when a page is rolled back', function (): void {
    $page = layoutRollbackPage();
    $hero = Widget::factory()->create();
    $features = Widget::factory()->create();
    $pricing = Widget::factory()->create();

    WidgetAsset::factory()->page($page, 'main')->widget($hero)->asset($page)->create(['order' => 1, 'meta' => ['tone' => 'bold']]);
    $removed = WidgetAsset::factory()->page($page, 'main')->widget($features)->asset($page)->create(['order' => 2]);
    $targetVersion = recordLayoutRevision($page);
    $layoutAtTarget = pageLayout($page);

    $removed->delete();
    WidgetAsset::query()->where('widget_id', $hero->id)->update(['order' => 5, 'meta' => json_encode(['tone' => 'quiet'])]);
    WidgetAsset::factory()->page($page, 'sidebar')->widget($pricing)->asset($page)->create(['order' => 1]);
    recordLayoutRevision($page->refresh());

    $preview = BuildRollbackPreviewAction::run($page->refresh(), $targetVersion);
    $paths = array_map(static fn (RollbackFieldChangeData $change): string => $change->path, $preview->fields);

    expect($paths)->toContain('layoutWidgets');

    ApplyRollbackAction::run($page->refresh(), $targetVersion);

    expect(pageLayout($page))->toBe($layoutAtTarget);
});

it('keeps the current layout when restoring a revision recorded before layouts were captured', function (): void {
    $page = layoutRollbackPage();
    $widget = Widget::factory()->create();
    WidgetAsset::factory()->page($page, 'main')->widget($widget)->asset($page)->create(['order' => 1]);

    $serializer = resolve(PageStateSerializer::class);
    $legacyState = $serializer->capture($page->refresh());
    unset($legacyState['layoutWidgets']);

    $serializer->restore($page->refresh(), $legacyState);

    expect(pageLayout($page))->toHaveCount(1);
});

it('only restores widgets placed on the rolled-back page', function (): void {
    $page = layoutRollbackPage();
    $otherPage = layoutRollbackPage();
    $widget = Widget::factory()->create();

    WidgetAsset::factory()->page($page, 'main')->widget($widget)->asset($page)->create(['order' => 1]);
    WidgetAsset::factory()->page($otherPage, 'main')->widget($widget)->asset($otherPage)->create(['order' => 1]);
    $targetVersion = recordLayoutRevision($page);

    WidgetAsset::factory()->page($page, 'main')->widget(Widget::factory()->create())->asset($page)->create(['order' => 2]);
    recordLayoutRevision($page->refresh());

    ApplyRollbackAction::run($page->refresh(), $targetVersion);

    expect(pageLayout($page))->toHaveCount(1)
        ->and(pageLayout($otherPage))->toHaveCount(1);
});

it('shows layout widget changes in a version comparison and restores them from that version', function (): void {
    $page = layoutRollbackPage();
    $hero = Widget::factory()->create();
    WidgetAsset::factory()->page($page, 'main')->widget($hero)->asset($page)->create(['order' => 1]);
    $withHero = recordLayoutRevision($page);
    $layoutWithHero = pageLayout($page);

    WidgetAsset::factory()->page($page, 'sidebar')->widget(Widget::factory()->create())->asset($page)->create(['order' => 1]);
    $withSidebar = recordLayoutRevision($page->refresh());

    $comparison = CompareWithPreviousVersionAction::run($page->refresh(), $withSidebar);
    $layoutChange = collect($comparison->fields)->firstWhere('path', 'layoutWidgets');

    throw_unless($layoutChange instanceof RollbackFieldChangeData, RuntimeException::class, 'The comparison must list the layout widget change.');

    expect($comparison->previousVersion)->toBe($withHero)
        ->and(count((array) $layoutChange->after))->toBe(count((array) $layoutChange->before) + 1);

    ApplyRollbackAction::run($page->refresh(), $withHero);

    expect(pageLayout($page))->toBe($layoutWithHero);
});
