<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\ApplyLayoutSidebarWidgetContributionsAction;
use Capell\LayoutBuilder\Contracts\LayoutSidebarWidgetContributor;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Tests\Fixtures\StandardLayoutSidebarContributor;

beforeEach(function (): void {
    app()->bind(StandardLayoutSidebarContributor::class, fn (): StandardLayoutSidebarContributor => new StandardLayoutSidebarContributor);
    app()->tag([StandardLayoutSidebarContributor::class], LayoutSidebarWidgetContributor::TAG);
});

it('keeps a dynamic page slot beside contributed sidebars on framework starter layouts', function (string $key, ?string $starterWidgetKey): void {
    $mainWidgets = $starterWidgetKey === null ? [] : [['widget_key' => $starterWidgetKey, 'occurrence' => 1]];
    Widget::factory()->create(['key' => 'standard-sidebar']);
    $slot = Widget::factory()->create(['key' => 'page-slot', 'meta' => ['type' => 'slot', 'preserve' => 'owned settings']]);
    $slotBefore = $slot->refresh()->getAttributes();
    $layout = Layout::factory()->create(['key' => $key]);
    $containers = $mainWidgets === [] ? [] : ['main' => ['widgets' => $mainWidgets, 'meta' => ['colspan' => 12]]];
    $layout->forceFill(['containers' => $containers])->save();

    ApplyLayoutSidebarWidgetContributionsAction::run($layout);
    $installed = $layout->fresh()?->containers;

    $this->assertIsArray($installed);

    $installedMain = $installed['main'] ?? null;
    $installedSidebar = $installed['sidebar'] ?? null;
    $this->assertIsArray($installedMain);
    $this->assertIsArray($installedSidebar);

    $mainMeta = $installedMain['meta'] ?? null;
    $this->assertIsArray($mainMeta);
    expect(array_keys($installed))->toBe(['main', 'sidebar'])
        ->and($mainMeta['colspan'] ?? null)->toBe(9)
        ->and($installedMain['widgets'] ?? null)->toBe([...$mainWidgets, ['widget_key' => 'page-slot', 'occurrence' => 1]])
        ->and($installedSidebar['widgets'] ?? null)->toBe([['widget_key' => 'standard-sidebar']])
        ->and($slot->fresh()?->getAttributes())->toBe($slotBefore);

    ApplyLayoutSidebarWidgetContributionsAction::run($layout->refresh());
    expect($layout->fresh()?->containers)->toBe($installed);
})->with([
    'empty results layout' => ['results', null],
    'default page-content starter' => ['default', 'page-content'],
]);

it('preserves an authored main container when contributing a sidebar', function (): void {
    Widget::factory()->create(['key' => 'standard-sidebar']);
    $main = ['widgets' => [['widget_key' => 'custom-main', 'meta' => ['authored' => true]]], 'meta' => ['colspan' => 12, 'padding' => ['lg']]];
    $layout = Layout::factory()->create(['key' => 'results']);
    $layout->forceFill(['containers' => ['main' => $main]])->save();

    ApplyLayoutSidebarWidgetContributionsAction::run($layout);

    expect($layout->fresh()?->containers['main'])->toBe($main);
});

it('does not introduce a main slot when no contributed widget is available', function (): void {
    $layout = Layout::factory()->create(['key' => 'results']);
    $layout->forceFill(['containers' => []])->save();

    ApplyLayoutSidebarWidgetContributionsAction::run($layout);

    expect($layout->fresh()?->containers)->not->toHaveKey('main')
        ->and(Widget::query()->where('key', 'page-slot')->exists())->toBeFalse();
});

it('creates the canonical slot only when a standard main needs it', function (): void {
    Widget::factory()->create(['key' => 'standard-sidebar']);
    $layout = Layout::factory()->create(['key' => 'results']);
    $layout->forceFill(['containers' => []])->save();

    ApplyLayoutSidebarWidgetContributionsAction::run($layout);

    $slot = Widget::query()->where('key', 'page-slot')->firstOrFail();
    expect(data_get($slot->meta, 'type'))->toBe('slot')
        ->and($layout->fresh()?->containers['main']['widgets'])->toContain(['widget_key' => 'page-slot', 'occurrence' => 1]);
});

it('preserves authored settings on an otherwise starter-shaped main', function (): void {
    Widget::factory()->create(['key' => 'standard-sidebar']);
    $main = ['widgets' => [['widget_key' => 'page-content', 'occurrence' => 1]], 'meta' => ['colspan' => 12, 'padding' => ['lg']]];
    $layout = Layout::factory()->create(['key' => 'default']);
    $layout->forceFill(['containers' => ['main' => $main]])->save();

    ApplyLayoutSidebarWidgetContributionsAction::run($layout);

    expect($layout->fresh()?->containers['main'])->toBe($main)
        ->and(Widget::query()->where('key', 'page-slot')->exists())->toBeFalse();
});

it('preserves an existing reserved-key widget with a different purpose', function (): void {
    Widget::factory()->create(['key' => 'standard-sidebar']);
    $widget = Widget::factory()->create(['key' => 'page-slot', 'meta' => ['custom' => true]]);
    $before = $widget->refresh()->getAttributes();
    $layout = Layout::factory()->create(['key' => 'results']);
    $layout->forceFill(['containers' => []])->save();

    ApplyLayoutSidebarWidgetContributionsAction::run($layout);

    expect($widget->fresh()?->getAttributes())->toBe($before)
        ->and($layout->fresh()?->containers)->not->toHaveKey('main');
});

it('rolls back a new canonical slot when the layout write fails', function (): void {
    Widget::factory()->create(['key' => 'standard-sidebar']);
    $layout = Layout::factory()->create(['key' => 'results']);
    $layout->forceFill(['containers' => []])->save();
    $dispatcher = Layout::getEventDispatcher();
    $this->assertNotNull($dispatcher);
    Layout::setEventDispatcher(clone $dispatcher);

    try {
        Layout::updating(static function (Layout $updating) use ($layout): void {
            if ($updating->getKey() === $layout->getKey()) {
                throw new RuntimeException('Layout write failed.');
            }
        });

        expect(fn () => ApplyLayoutSidebarWidgetContributionsAction::run($layout))
            ->toThrow(RuntimeException::class, 'Layout write failed.');
    } finally {
        Layout::setEventDispatcher($dispatcher);
    }

    expect(Widget::query()->where('key', 'page-slot')->exists())->toBeFalse()
        ->and($layout->fresh()?->containers)->toBe([]);

    ApplyLayoutSidebarWidgetContributionsAction::run($layout->refresh());

    expect(Widget::query()->where('key', 'page-slot')->count())->toBe(1)
        ->and(data_get($layout->fresh()?->containers, 'main.widgets'))->toContain(['widget_key' => 'page-slot', 'occurrence' => 1]);
});

it('leaves a starter and authored sidebar alone when no applicable contribution is available', function (): void {
    Widget::factory()->create(['key' => 'authored-sidebar']);
    $main = ['widgets' => [['widget_key' => 'page-content', 'occurrence' => 1]], 'meta' => ['colspan' => 12]];
    $sidebar = ['widgets' => [['widget_key' => 'authored-sidebar']], 'meta' => ['colspan' => 3]];
    $layout = Layout::factory()->create(['key' => 'default']);
    $layout->forceFill(['containers' => ['main' => $main, 'sidebar' => $sidebar]])->save();

    ApplyLayoutSidebarWidgetContributionsAction::run($layout);

    expect($layout->fresh()?->containers)->toBe(['main' => $main, 'sidebar' => $sidebar])
        ->and(Widget::query()->where('key', 'page-slot')->exists())->toBeFalse();
});
