<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Layout\Models\Widget;

it('has many widgets', function (): void {
    Widget::factory()->create(['key' => 'test']);

    $layout = Layout::factory()->create([
        'containers' => [
            'first' => ['widgets' => [['widget_key' => 'test']]],
            'second' => ['widgets' => []],
            'third' => ['widgets' => [['widget_key' => 'test2']]],
        ],
    ]);

    expect($layout->refresh())
        ->widgets->toBe(['test', 'test2']);
});

it('returns layoutWidgets via BelongsToJson relation', function (): void {
    $widgetA = Widget::factory()->create(['key' => 'widget-a']);
    $widgetB = Widget::factory()->create(['key' => 'widget-b']);

    /** @var Layout $layout */
    $layout = Layout::factory()->state([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => 'widget-a'],
                    ['widget_key' => 'widget-b'],
                ],
            ],
        ]
    ])
    ->create();

    $fetched = $layout->layoutWidgets;

    expect($fetched)->toHaveCount(2)
        ->and($fetched->pluck('key')->all())->toEqualCanonicalizing([$widgetA->key, $widgetB->key]);
});
