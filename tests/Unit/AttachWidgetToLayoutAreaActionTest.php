<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Actions\AttachWidgetToLayoutAreaAction;

it('creates an area container and attaches the widget', function (): void {
    $layout = Layout::factory()->create(['containers' => []]);

    AttachWidgetToLayoutAreaAction::run(
        layout: $layout,
        area: 'Header',
        widgetKey: 'demo-header-links',
        containerKey: 'Demo Header',
        containerMeta: ['container' => 'full'],
        containerName: 'Demo header links',
    );

    $layout->refresh();

    expect($layout->containers)->toHaveKey('demo-header')
        ->and($layout->containers['demo-header']['name'])->toBe('Demo header links')
        ->and($layout->containers['demo-header']['meta'])->toMatchArray([
            'area' => 'header',
            'container' => 'full',
        ])
        ->and($layout->containers['demo-header']['widgets'])->toBe([
            ['widget_key' => 'demo-header-links', 'occurrence' => 1],
        ]);
});

it('preserves existing container metadata and avoids duplicate occurrences', function (): void {
    $layout = Layout::factory()->create([
        'containers' => [
            'header-links' => [
                'name' => 'Existing header links',
                'meta' => [
                    'area' => 'header',
                    'html_class' => 'items-center',
                ],
                'widgets' => [
                    ['widget_key' => 'demo-header-links', 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    AttachWidgetToLayoutAreaAction::run(
        layout: $layout,
        area: 'header',
        widgetKey: 'demo-header-links',
        containerKey: 'header-links',
        containerMeta: ['container' => 'full'],
    );

    $layout->refresh();

    expect($layout->containers['header-links']['name'])->toBe('Existing header links')
        ->and($layout->containers['header-links']['meta'])->toMatchArray([
            'area' => 'header',
            'container' => 'full',
            'html_class' => 'items-center',
        ])
        ->and($layout->containers['header-links']['widgets'])->toBe([
            ['widget_key' => 'demo-header-links', 'occurrence' => 1],
        ]);
});

it('allows multiple occurrences of the same widget in an area container', function (): void {
    $layout = Layout::factory()->create([
        'containers' => [
            'header' => [
                'meta' => ['area' => 'header'],
                'widgets' => [
                    ['widget_key' => 'demo-header-links', 'occurrence' => 1],
                ],
            ],
        ],
    ]);

    AttachWidgetToLayoutAreaAction::run(
        layout: $layout,
        area: 'header',
        widgetKey: 'demo-header-links',
        occurrence: 2,
    );

    $layout->refresh();

    expect($layout->containers['header']['widgets'])->toBe([
        ['widget_key' => 'demo-header-links', 'occurrence' => 1],
        ['widget_key' => 'demo-header-links', 'occurrence' => 2],
    ]);
});
