<?php

declare(strict_types=1);

use Capell\Frontend\Data\FrontendAssetManifestData;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Data\PublicPageRenderData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\Frontend\Support\Render\PublicViewQueryGuard;
use Capell\LayoutBuilder\Actions\WidgetExtensions\RenderPublicWidgetExtensionAction;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleRenderData;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Illuminate\Support\Facades\View;

beforeEach(function (): void {
    $this->extensionViewRoot = sys_get_temp_dir() . '/capell-widget-render-' . bin2hex(random_bytes(5));
    mkdir($this->extensionViewRoot . '/theme/widgets/capell-app', 0777, true);
    mkdir($this->extensionViewRoot . '/fallback', 0777, true);
    file_put_contents($this->extensionViewRoot . '/theme/widgets/capell-app/slideshow.blade.php', 'THEME {{ $widget->title }}');
    file_put_contents($this->extensionViewRoot . '/fallback/widget.blade.php', 'FALLBACK {{ $widget->title }}');
    View::addNamespace('capell', $this->extensionViewRoot . '/theme');
    View::addNamespace('widget-render-test', $this->extensionViewRoot . '/fallback');
});

afterEach(function (): void {
    collect(glob($this->extensionViewRoot . '/**/*') ?: [])->reverse()->each(static function (string $path): void {
        is_dir($path) ? @rmdir($path) : @unlink($path);
    });
    @rmdir($this->extensionViewRoot . '/theme/widgets/capell-app');
    @rmdir($this->extensionViewRoot . '/theme/widgets');
    @rmdir($this->extensionViewRoot . '/theme');
    @rmdir($this->extensionViewRoot . '/fallback');
    @rmdir($this->extensionViewRoot);
});

it('renders the request theme view with only typed public data', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        fallbackView: 'widget-render-test::widget',
    ));

    $renderData = widgetExtensionPublicRenderData([
        'opaque-instance' => new ExampleRenderData('<Unsafe title>'),
    ]);
    $html = resolve(RenderPublicWidgetExtensionAction::class)->render([
        'type' => 'capell-app.slideshow',
        'data' => [
            'title' => 'raw secret',
            '__capell' => ['instance_id' => 'opaque-instance', 'state_version' => 2],
        ],
    ], $renderData);

    expect($html)->toContain('THEME &lt;Unsafe title&gt;')
        ->not->toContain('raw secret', 'opaque-instance', 'state_version', '__capell', 'capell-app.slideshow');
});

it('uses a generic inert fallback for missing or invalid typed payloads', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        fallbackView: 'widget-render-test::widget',
    ));

    $html = resolve(RenderPublicWidgetExtensionAction::class)->render([
        'type' => 'capell-app.slideshow',
        'data' => ['__capell' => ['instance_id' => 'missing']],
    ], widgetExtensionPublicRenderData([]));

    expect($html)->toContain('role="status"')
        ->not->toContain('missing', 'capell-app.slideshow');
});

it('falls back safely when the package fallback view is missing', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        fallbackView: 'widget-render-test::missing',
    ));

    $html = resolve(RenderPublicWidgetExtensionAction::class)->render([
        'type' => 'capell-app.slideshow',
        'data' => ['__capell' => ['instance_id' => 'present']],
    ], widgetExtensionPublicRenderData(['present' => new ExampleRenderData('Safe')]));

    expect($html)->toContain('role="status"')->not->toContain('Safe', 'present');
});

it('executes package and theme Blade inside the public query guard without queries', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        fallbackView: 'widget-render-test::widget',
    ));
    $renderData = widgetExtensionPublicRenderData(['guarded' => new ExampleRenderData('Guarded')]);
    config()->set('capell-frontend.public_view_query_guard.enabled', true);
    config()->set('capell-frontend.public_view_query_guard.mode', 'exception');

    $html = resolve(PublicViewQueryGuard::class)->guard(
        new FrontendRenderContextData(null, null, null, null, null, publicRenderData: $renderData),
        fn (): string => resolve(RenderPublicWidgetExtensionAction::class)->render([
            'type' => 'capell-app.slideshow',
            'data' => ['__capell' => ['instance_id' => 'guarded']],
        ], $renderData),
    );

    expect($html)->toContain('THEME Guarded');
});

/** @param array<string, object> $payloads */
function widgetExtensionPublicRenderData(array $payloads): PublicPageRenderData
{
    $runtime = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);

    return new PublicPageRenderData(
        page: null,
        site: null,
        language: null,
        layout: null,
        theme: null,
        layoutGraph: null,
        runtimeManifest: $runtime,
        assetManifest: new FrontendAssetManifestData([], [], [], [], $runtime),
        surrogateKeys: [],
        contentWidgetPayloads: $payloads,
    );
}
