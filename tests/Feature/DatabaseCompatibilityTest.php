<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Models\Widget;

it('counts layouts containing each widget key through semantic JSON search', function (): void {
    $hero = Widget::factory()->create(['key' => 'hero']);
    $heroBanner = Widget::factory()->create(['key' => 'hero-banner']);
    $callToAction = Widget::factory()->create(['key' => 'call-to-action']);
    $unused = Widget::factory()->create(['key' => 'unused']);

    Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $hero->key, 'occurrence' => 1],
                    ['widget_key' => $callToAction->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);
    Layout::factory()->create([
        'containers' => [
            'sidebar' => [
                'widgets' => [
                    ['occurrence' => 1, 'widget_key' => $hero->key],
                ],
            ],
        ],
    ]);
    Layout::factory()->create([
        'containers' => [
            'announcement' => [
                'widgets' => [],
                'meta' => ['label' => $hero->key],
            ],
        ],
    ]);
    Layout::factory()->create([
        'containers' => [
            'main' => [
                'widgets' => [
                    ['widget_key' => $heroBanner->key, 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    $widgets = Widget::query()
        ->withLayoutsCount()
        ->whereKey([$hero->getKey(), $heroBanner->getKey(), $callToAction->getKey(), $unused->getKey()])
        ->get()
        ->keyBy('key');

    $layoutsCount = static function (mixed $widget): int {
        if (! $widget instanceof Widget) {
            throw new UnexpectedValueException('Expected a layout builder widget.');
        }

        $count = $widget->getAttribute('layouts_count');

        if (! is_numeric($count)) {
            throw new UnexpectedValueException('Expected a numeric layouts count.');
        }

        return (int) $count;
    };

    expect($layoutsCount($widgets->get('hero')))->toBe(2)
        ->and($layoutsCount($widgets->get('hero-banner')))->toBe(1)
        ->and($layoutsCount($widgets->get('call-to-action')))->toBe(1)
        ->and($layoutsCount($widgets->get('unused')))->toBe(0);
});
