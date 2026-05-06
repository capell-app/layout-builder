<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\AddHeroWidgetToLayoutAction;
use Capell\LayoutBuilder\Models\Widget;

it('prepends a hero container to a layout that has none', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => ['widgets' => []],
        ],
    ]);

    AddHeroWidgetToLayoutAction::run($widget, $layout);

    $layout->refresh();
    $containerKeys = array_keys($layout->containers);

    expect($containerKeys[0])->toBe('hero')
        ->and($layout->containers)->toHaveKey('main');
});

it('stores the hero widget entry inside the newly created hero container', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create(['containers' => ['main' => ['widgets' => []]]]);

    AddHeroWidgetToLayoutAction::run($widget, $layout);

    $layout->refresh();
    $heroWidgets = $layout->containers['hero']['widgets'];

    expect(collect($heroWidgets)->pluck('widget_key')->all())->toContain('hero');
});

it('does not duplicate the widget when called a second time on the same layout', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero']);
    $layout = Layout::factory()->create(['containers' => ['main' => ['widgets' => []]]]);

    AddHeroWidgetToLayoutAction::run($widget, $layout);
    AddHeroWidgetToLayoutAction::run($widget, $layout->fresh());

    $layout->refresh();

    expect($layout->containers['hero']['widgets'])->toHaveCount(1);
});

it('does not recreate the hero container when it already exists', function (): void {
    $widget = Widget::factory()->create(['key' => 'hero']);
    $existingHeroContainer = [
        'meta' => ['colspan' => 6],
        'widgets' => [],
    ];
    $layout = Layout::factory()->create([
        'containers' => [
            'hero' => $existingHeroContainer,
            'main' => ['widgets' => []],
        ],
    ]);

    AddHeroWidgetToLayoutAction::run($widget, $layout);

    $layout->refresh();

    expect($layout->containers['hero']['meta']['colspan'])->toBe(6);
});
