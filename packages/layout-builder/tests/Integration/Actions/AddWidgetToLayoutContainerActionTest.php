<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\AddWidgetToLayoutContainerAction;
use Capell\LayoutBuilder\Models\Widget;

it('adds the widget to an existing container', function (): void {
    $widget = Widget::factory()->create(['key' => 'test-widget']);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => ['widgets' => []],
        ],
    ]);

    AddWidgetToLayoutContainerAction::run($widget, $layout, 'main');

    $layout->refresh();

    expect($layout->containers['main']['widgets'])
        ->toHaveCount(1)
        ->and($layout->containers['main']['widgets'][0]['widget_key'])->toBe('test-widget')
        ->and($layout->containers['main']['widgets'][0]['occurrence'])->toBe(1);
});

it('sets occurrence to 2 when the same widget is added a second time', function (): void {
    $widget = Widget::factory()->create(['key' => 'repeated-widget']);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'repeated-widget', 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    AddWidgetToLayoutContainerAction::run($widget, $layout, 'main');

    $layout->refresh();

    expect($layout->containers['main']['widgets'])
        ->toHaveCount(2)
        ->and($layout->containers['main']['widgets'][1]['widget_key'])->toBe('repeated-widget')
        ->and($layout->containers['main']['widgets'][1]['occurrence'])->toBe(2);
});

it('skips adding the widget when skipExists is true and widget already exists', function (): void {
    $widget = Widget::factory()->create(['key' => 'existing-widget']);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'existing-widget', 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    AddWidgetToLayoutContainerAction::run($widget, $layout, 'main', skipExists: true);

    $layout->refresh();

    expect($layout->containers['main']['widgets'])->toHaveCount(1);
});

it('throws a RuntimeException when the container does not exist', function (): void {
    $widget = Widget::factory()->create(['key' => 'any-widget']);
    $layout = Layout::factory()->create([
        'containers' => [
            'main' => ['widgets' => []],
        ],
    ]);

    AddWidgetToLayoutContainerAction::run($widget, $layout, 'missing-container');
})->throws(RuntimeException::class, "Container 'missing-container' not found in layout.");
