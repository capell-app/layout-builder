<?php

declare(strict_types=1);

use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

beforeEach(function (): void {
    $this->widgetGateViewRoot = sys_get_temp_dir() . '/capell-widget-gate-' . bin2hex(random_bytes(6));
    mkdir($this->widgetGateViewRoot, 0777, true);
    file_put_contents($this->widgetGateViewRoot . '/extension.blade.php', 'RAW_EXTENSION_SECRET {{ $title ?? "" }}');
    file_put_contents($this->widgetGateViewRoot . '/legacy.blade.php', 'LEGACY_WIDGET {{ $title ?? "" }}');
    View::addNamespace('widget-gate-test', $this->widgetGateViewRoot);
    Blade::anonymousComponentPath($this->widgetGateViewRoot, 'widget-gate-test');
});

afterEach(function (): void {
    foreach (glob($this->widgetGateViewRoot . '/*') ?: [] as $file) {
        unlink($file);
    }

    rmdir($this->widgetGateViewRoot);
});

it('does not pass raw extension state to package or theme views', function (string $view, array $data): void {
    app(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        fallbackView: 'widget-gate-test::extension',
    ));

    $html = view($view, $data)->render();

    expect($html)->not->toContain('RAW_EXTENSION_SECRET')
        ->and($html)->not->toContain('unhydrated title')
        ->and($html)->not->toContain('capell-app.slideshow')
        ->and($html)->not->toContain('capell-app/widget-slideshow')
        ->and($html)->not->toContain('instance-secret')
        ->and($html)->not->toContain('editor-secret');
})->with([
    'widget list' => [
        'capell-layout-builder::components.layout-widgets.index',
        [
            'widgets' => [[
                'type' => 'capell-app.slideshow',
                'data' => [
                    'title' => 'unhydrated title',
                    'package' => 'capell-app/widget-slideshow',
                    '__capell' => [
                        'instance_id' => 'instance-secret',
                        'editor_url' => 'editor-secret',
                    ],
                ],
            ]],
            'context' => [],
        ],
    ],
    'interaction target' => [
        'capell-layout-builder::components.layout-widgets.interaction-target',
        [
            'widgetData' => [
                'type' => 'capell-app.slideshow',
                'data' => [
                    'title' => 'unhydrated title',
                    'package' => 'capell-app/widget-slideshow',
                    '__capell' => [
                        'instance_id' => 'instance-secret',
                        'editor_url' => 'editor-secret',
                    ],
                ],
            ],
            'context' => [],
        ],
    ],
]);

it('continues rendering ordinary legacy widgets', function (): void {
    app(LayoutWidgetRegistry::class)->register(
        'legacy-banner',
        LayoutWidgetTarget::FrontendBlade,
        'widget-gate-test::legacy',
    );

    $html = view('capell-layout-builder::components.layout-widgets.index', [
        'widgets' => [[
            'type' => 'legacy-banner',
            'data' => ['title' => 'safe legacy title'],
        ]],
        'context' => [],
    ])->render();

    expect($html)->toContain('LEGACY_WIDGET safe legacy title');
});
