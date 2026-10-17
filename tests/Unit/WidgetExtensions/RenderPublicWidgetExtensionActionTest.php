<?php

declare(strict_types=1);

use Capell\Frontend\Data\Assets\FrontendResourcePlanData;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Data\PublicPageRenderData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\Frontend\Support\Render\PublicViewQueryGuard;
use Capell\Frontend\Support\View\ThemeViewRegistrar;
use Capell\LayoutBuilder\Actions\LayoutWidgets\BuildLayoutWidgetResourceUsagesAction;
use Capell\LayoutBuilder\Actions\WidgetExtensions\RenderPublicWidgetExtensionAction;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleRenderData;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Dom\HTMLDocument;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\View;
use Illuminate\View\FileViewFinder;

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
    foreach (['active', 'parent'] as $themeDirectory) {
        @unlink($this->extensionViewRoot . '/' . $themeDirectory . '/widgets/capell-app/slideshow.blade.php');
        @rmdir($this->extensionViewRoot . '/' . $themeDirectory . '/widgets/capell-app');
        @rmdir($this->extensionViewRoot . '/' . $themeDirectory . '/widgets');
        @rmdir($this->extensionViewRoot . '/' . $themeDirectory);
    }

    @rmdir($this->extensionViewRoot . '/missing');
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

it('switches active parent and fallback views and payloads in one long-lived process without leakage', function (): void {
    $activeRoot = $this->extensionViewRoot . '/active';
    $parentRoot = $this->extensionViewRoot . '/parent';
    $missingRoot = $this->extensionViewRoot . '/missing';
    mkdir($activeRoot . '/widgets/capell-app', 0777, true);
    mkdir($parentRoot . '/widgets/capell-app', 0777, true);
    mkdir($missingRoot, 0777, true);
    file_put_contents($activeRoot . '/widgets/capell-app/slideshow.blade.php', 'ACTIVE {{ $widget->title }}');
    file_put_contents($parentRoot . '/widgets/capell-app/slideshow.blade.php', 'PARENT {{ $widget->title }}');

    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        fallbackView: 'widget-render-test::widget',
    ));
    $widgetData = [
        'type' => 'capell-app.slideshow',
        'data' => ['__capell' => ['instance_id' => 'request-payload']],
    ];
    $renderer = resolve(RenderPublicWidgetExtensionAction::class);

    $viewFinder = resolve(Factory::class)->getFinder();
    expect($viewFinder)->toBeInstanceOf(FileViewFinder::class);
    if (! $viewFinder instanceof FileViewFinder) {
        throw new RuntimeException('Expected a file view finder.');
    }

    $themeViews = new ThemeViewRegistrar($viewFinder, []);
    $themeViews->register([$activeRoot, $parentRoot], 'active-widget-theme');

    $active = $renderer->render($widgetData, widgetExtensionPublicRenderData([
        'request-payload' => new ExampleRenderData('First'),
    ]));

    $themeViews->register([$missingRoot, $parentRoot], 'parent-widget-theme');
    $parent = $renderer->render($widgetData, widgetExtensionPublicRenderData([
        'request-payload' => new ExampleRenderData('Second'),
    ]));

    $themeViews->register([$missingRoot], 'missing-widget-theme');
    $fallback = $renderer->render($widgetData, widgetExtensionPublicRenderData([
        'request-payload' => new ExampleRenderData('Third'),
    ]));

    expect($active)->toContain('ACTIVE First')->not->toContain('Second', 'Third', 'PARENT', 'FALLBACK')
        ->and($parent)->toContain('PARENT Second')->not->toContain('First', 'Third', 'ACTIVE', 'FALLBACK')
        ->and($fallback)->toContain('FALLBACK Third')->not->toContain('First', 'Second', 'ACTIVE', 'PARENT');
});

it('gives each resource group the same activation strategy in the wrapper and collector', function (array $capell, array $expected): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make(
        fallbackView: 'widget-render-test::widget',
    ));
    $widget = [
        'type' => 'capell-app.slideshow',
        'data' => ['__capell' => ['instance_id' => 'strategy-instance', ...$capell]],
    ];
    $usages = BuildLayoutWidgetResourceUsagesAction::run([$widget], LayoutWidgetTarget::FrontendBlade);
    $html = resolve(RenderPublicWidgetExtensionAction::class)->render(
        $widget,
        widgetExtensionPublicRenderData(['strategy-instance' => new ExampleRenderData('Group strategies')]),
    );
    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    $strategies = [];

    foreach ($document->querySelectorAll('[data-capell-widget-runtime]') as $runtime) {
        $ids = json_decode($runtime->getAttribute('data-capell-widget-resources') ?: '[]', true, flags: JSON_THROW_ON_ERROR);
        $settings = json_decode($runtime->getAttribute('data-capell-widget-settings') ?? '{}', true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($ids) || ! is_array($settings)) {
            throw new LogicException('Expected resource ids and runtime settings to be JSON arrays or objects.');
        }

        foreach ($ids as $id) {
            if (! is_string($id)) {
                throw new LogicException('Expected an opaque string resource id.');
            }

            expect($strategies)->not->toHaveKey($id);
            $strategies[$id] = $settings['loading_strategy'];
        }

        expect($runtime->textContent)->toContain('Group strategies');
    }

    foreach ($usages as $index => $usage) {
        expect($usage->presentation->loadingStrategy->value)->toBe($expected[$index])
            ->and($strategies[$usage->publicId] ?? null)->toBe($expected[$index]);
    }

    expect($strategies)->toHaveCount(2)
        ->and($html)->not->toContain('strategy-instance', '__capell', 'loading_overrides', 'capell-app.slideshow');
})->with([
    'definition per-group defaults' => [[], ['visible', 'interaction']],
    'global instance strategy' => [['presentation' => ['loading_strategy' => 'idle']], ['idle', 'idle']],
    'per-group instance strategies' => [[
        'presentation' => ['loading_strategy' => 'visible'],
        'resources' => ['loading_overrides' => [
            ['group' => 'capell-app.widget-slideshow', 'loading_strategy' => 'idle'],
            ['group' => 'capell-app.widget-slideshow.interaction', 'loading_strategy' => 'eager'],
            ['group' => 'undeclared-group', 'loading_strategy' => 'interaction'],
        ]],
    ], ['idle', 'eager']],
    'invalid overrides ignored' => [[
        'resources' => ['loading_overrides' => [
            ['group' => 'capell-app.widget-slideshow.interaction', 'loading_strategy' => 'invalid'],
        ]],
    ], ['visible', 'interaction']],
]);

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
        resourcePlan: new FrontendResourcePlanData([], [], [], [], [], [], [], hash('sha256', 'empty')),
        surrogateKeys: [],
        contentWidgetPayloads: $payloads,
    );
}
