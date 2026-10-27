<?php

declare(strict_types=1);

use Capell\Core\Data\RenderableDefinitionData;
use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\PresentationDeliveryMode;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Support\Renderables\RenderableRegistry;
use Capell\Core\Support\Themes\LayoutViewRegistry;
use Capell\Frontend\Contracts\Fragments\PublicFragmentReferenceCodec;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Contracts\PublicLayoutRenderPreparer;
use Capell\Frontend\Data\Assets\FrontendResourcePlanData;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\Frontend\Data\FrontendRenderPayload;
use Capell\Frontend\Data\FrontendRuntimeManifestData;
use Capell\Frontend\Data\PublicPageRenderData;
use Capell\Frontend\Enums\RenderingStrategyEnum;
use Capell\Frontend\Facades\Frontend;
use Capell\Frontend\Support\Render\BladeFrontendResponseRenderer;
use Capell\Frontend\Support\Render\PublicRenderDataScope;
use Capell\Frontend\Support\Render\PublicViewQueryGuard;
use Capell\LayoutBuilder\Actions\BuildPublicLayoutGraphAction;
use Capell\LayoutBuilder\Actions\Fragments\BuildLayoutBuilderFragmentReferenceAction;
use Capell\LayoutBuilder\Actions\ResolvePublicWidgetRenderContextAction;
use Capell\LayoutBuilder\Contracts\PublicLayoutWidgetPayloadContributor;
use Capell\LayoutBuilder\Listeners\LayoutLoaded;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\CapellLayoutManager;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutRenderPreparer;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Capell\LayoutBuilder\Support\Loader\LayoutLoader;
use Capell\LayoutBuilder\Tests\Fixtures\PreparedSectionPayloadContributor;
use Dom\HTMLDocument;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function (): void {
    CapellLayoutManager::clearContainerWidgets();
    $path = dirname(__DIR__, 2) . '/Fixtures/views/prepared-layout';
    View::addNamespace('lb-prepared', $path);
    Blade::anonymousComponentPath($path, 'lb-prepared');
    $layouts = resolve(LayoutViewRegistry::class);
    $layouts->registerLayout('lb-prepared::layout', 'Prepared layout');
    $layouts->registerLayout('lb-prepared::with-area', 'Prepared layout with area');
    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(
        key: 'prepared-test-widget',
        type: 'layout-widget',
        blade: 'lb-prepared::probe',
    ));
    config()->set('capell-frontend.public_view_query_guard.enabled', true);
    config()->set('capell-frontend.public_view_query_guard.mode', 'exception');
});

afterEach(function (): void {
    CapellLayoutManager::clearContainerWidgets();
});

/** @return array{FrontendRenderContextData, Widget} */
function preparedLayoutFixture(bool $presentation = true, bool $foundation = false): array
{
    if (! $foundation) {
        View::replaceNamespace('capell', [dirname(__DIR__, 5) . '/vendor/capell-app/frontend/resources/views']);
        Blade::component(Capell\Frontend\View\Components\Layout::class, 'capell::layout');
        Blade::component('capell::components.layout.main', 'capell::layout.main');
    }

    $language = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->id]);
    $widget = Widget::factory()->create([
        'key' => 'prepared-probe',
        'component' => 'prepared-test-widget',
        'meta' => ['admin_schema' => ['private' => true], 'signed_url' => '/admin/secret'],
    ]);
    $layout = Layout::factory()->site($site)->create([
        'meta' => ['layout_file' => 'lb-prepared::layout'],
        'containers' => [
            'main' => [
                'meta' => $presentation ? ['html_class' => 'review-shell', 'padding' => ['sm']] : [],
                'widgets' => [
                    ['widget_key' => $widget->key, 'occurrence' => 1, 'meta' => ['admin_schema' => 'private', 'signed_url' => '/admin/secret']],
                    ['widget_key' => $widget->key, 'occurrence' => 2],
                ],
            ],
            'sibling' => [
                'meta' => $presentation ? ['html_class' => 'sibling-shell'] : [],
                'widgets' => [['widget_key' => $widget->key, 'occurrence' => 1]],
            ],
        ],
    ]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $page->load('translation', 'blueprint');
    $page->setRelation('site', $site);

    $theme = Theme::factory()->make(['key' => 'default', 'meta' => ['header' => false, 'footer' => false]]);
    $theme->load('blueprint');

    $reader = Mockery::mock(FrontendContextReader::class);
    $reader->shouldReceive('page')->andReturn($page);
    $reader->shouldReceive('site')->andReturn($site);
    $reader->shouldReceive('language')->andReturn($language);
    $reader->shouldReceive('layout')->andReturn($layout);
    $reader->shouldReceive('theme')->andReturn($theme);
    $reader->shouldReceive('getFrontendData')->andReturn(null)->byDefault();
    $reader->shouldReceive('setFrontendData')->andReturnSelf();
    $reader->shouldReceive('params')->andReturn([]);
    $reader->shouldReceive('isError')->andReturn(false);
    $reader->shouldReceive('renderPayload')->andReturn(FrontendRenderPayload::fromBag([]));
    Frontend::swap($reader);

    $runtime = FrontendRuntimeManifestData::forRenderingStrategy(RenderingStrategyEnum::BladeOnly);
    $data = new PublicPageRenderData(
        page: $page,
        site: $site,
        language: $language,
        layout: $layout,
        theme: $theme,
        layoutGraph: (object) ['containers' => [
            ['key' => 'main', 'widgets' => [
                ['key' => $widget->key, 'occurrence' => 1, 'data' => ['sections' => [
                    ['order' => 2, 'html' => '<section data-prepared="second">Second section</section>'],
                    ['order' => 1, 'html' => '<section data-prepared="first">Prepared introduction</section>'],
                ]]],
                ['key' => $widget->key, 'occurrence' => 2, 'data' => ['sections' => [
                    ['html' => '<section data-prepared="repeat">Repeated occurrence</section>'],
                ]]],
            ]],
            ['key' => 'sibling', 'widgets' => [['key' => $widget->key, 'occurrence' => 1, 'data' => []]]],
        ]],
        runtimeManifest: $runtime,
        resourcePlan: new FrontendResourcePlanData([], [], [], [], [], [], [], 'prepared-layout'),
        surrogateKeys: [],
    );

    return [new FrontendRenderContextData(
        page: $page,
        site: $site,
        language: $language,
        layout: $layout,
        theme: $theme,
        runtimeManifest: $runtime,
        publicRenderData: $data,
    ), $widget];
}

function renderPreparedLayout(FrontendRenderContextData $context): string
{
    $response = resolve(BladeFrontendResponseRenderer::class)->render($context);
    expect($response)->toBeInstanceOf(Response::class);
    assert($response instanceof Response);

    return (string) $response->getContent();
}

it('preserves every container and renders each prepared occurrence once with a third-party fallback', function (bool $foundation): void {
    [$context] = preparedLayoutFixture(foundation: $foundation);
    resolve(LayoutLoaded::class)->handle('loadedLayout', new stdClass);
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        if (resolve(PublicViewQueryGuard::class)->isActive()) {
            $queries[] = $query->sql;
        }
    });
    $html = renderPreparedLayout($context);
    $dom = HTMLDocument::createFromString($html, LIBXML_NOERROR);

    expect($dom->querySelectorAll('#layout-container-main.review-shell.capell-container-padding-sm')->length)->toBe(1)
        ->and($dom->querySelectorAll('#layout-container-sibling.sibling-shell')->length)->toBe(1)
        ->and($dom->querySelectorAll('#layout-container-main [data-prepared]')->length)->toBe(3)
        ->and($dom->querySelectorAll('[data-prepared="first"]')->length)->toBe(1)
        ->and($dom->querySelectorAll('[data-prepared="second"]')->length)->toBe(1)
        ->and($dom->querySelectorAll('[data-prepared="repeat"]')->length)->toBe(1)
        ->and($dom->querySelectorAll('[data-capell-widget-runtime] [data-prepared="first"]')->length)->toBe(1)
        ->and($dom->querySelectorAll('#layout-container-sibling [data-fallback="sibling:1"]')->length)->toBe(1)
        ->and($dom->querySelectorAll('#layout-container-main [data-fallback]')->length)->toBe(0)
        ->and($dom->querySelectorAll('[data-prepared="first"] + [data-prepared="second"]')->length)->toBe(1)
        ->and($queries)->toBe([]);

    foreach (['widget_key', 'admin_schema', 'signed_url', 'wire:snapshot', 'data-authoring', 'capell-layout-builder::'] as $marker) {
        expect($html)->not->toContain($marker);
    }
})->with(['Core layout' => false, 'Foundation layout' => true]);

it('supports legacy includes without implicitly opting authored layouts into prepared html', function (bool $prepared, bool $area): void {
    [$context] = preparedLayoutFixture();
    throw_unless($context->layout instanceof Layout, LogicException::class, 'Expected the layout fixture.');
    resolve(LayoutLoaded::class)->handle('loadedLayout', new stdClass);
    // A cached public graph alone must not opt a master or standalone include into this path.
    Frontend::shouldReceive('getFrontendData')->with('publicPageRenderData')->andReturn($context->publicRenderData);
    if ($prepared) {
        resolve(PublicLayoutRenderPreparer::class)->prepare($context);
    }

    $containers = $context->layout->containers ?? [];
    $container = $containers['main'] ?? [];
    throw_unless(is_array($container), LogicException::class, 'Expected the main container fixture.');
    $widgetData = LayoutWidgetData::normalizeMany($container['widgets'] ?? [])[0] ?? [];
    $widget = CapellLayoutManager::getStoredContainerWidget('main', 'prepared-probe');
    throw_unless($widget instanceof Widget, LogicException::class, 'Expected the preloaded widget fixture.');
    $viewData = [
        'layout' => $context->layout,
        'page' => $context->page,
        'component' => $widget->getComponent(),
        'container' => $container,
        'containerColspan' => 12,
        'containerKey' => 'main',
        'containerIndex' => 0,
        'containerWidth' => ContainerWidthEnum::Full,
        'loop' => null,
        'type' => 'blade',
        'widget' => $widget,
        'widgetData' => $widgetData,
        'widgetIndex' => 0,
        'pageSlot' => null,
    ];
    // Folio and existing third-party includes do not pass publicRenderData or widgetKey.
    // Core sets the render scope around its guarded render; a standalone include has none.
    $html = PublicRenderDataScope::during($prepared ? $context->publicRenderData : null, static fn (): mixed => resolve(PublicViewQueryGuard::class)->guard($context, static fn (): string => view(
        $area ? 'capell-layout-builder::components.layout.area' : 'capell-layout-builder::components.layout.widget',
        $viewData,
    )->render()));
    throw_unless(is_string($html), LogicException::class, 'Expected rendered public widget HTML.');
    $dom = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    expect($dom->querySelectorAll('[data-prepared]')->length)->toBe($prepared ? ($area ? 3 : 2) : 0)
        ->and($dom->querySelectorAll('[data-fallback]')->length)->toBe($prepared ? ($area ? 1 : 0) : ($area ? 3 : 1));
    foreach (['widget_key', 'admin_schema', 'signed_url'] as $marker) {
        expect($html)->not->toContain($marker);
    }
})->with(['Core prepared the layout' => true, 'authored or standalone include' => false])
    ->with(['legacy widget include' => false, 'layout area include' => true]);

it('prepares lazy references before the guard after losing request-scoped state on a render-data cache hit', function (bool $preparedPlaceholder): void {
    [$context, $widget] = preparedLayoutFixture();
    assert($context->layout instanceof Layout && $context->publicRenderData instanceof PublicPageRenderData);
    $containers = $context->layout->containers ?? [];
    Arr::set($containers, 'sibling.widgets.0.meta.presentation.delivery_mode', PresentationDeliveryMode::LazyFragment->value);
    $context->layout->containers = $containers;

    $reference = BuildLayoutBuilderFragmentReferenceAction::run('sibling', 1, $widget);
    expect($reference)->toBeString()->not->toBeEmpty();
    $placeholder = '<div id="prepared-lazy" data-deferred-fragment data-deferred-fragment-url="'
        . e(url('/_fragments/' . rawurlencode((string) $reference))) . '" class="deferred-fragment"></div>';
    if ($preparedPlaceholder) {
        $graph = $context->publicRenderData->layoutGraph;
        throw_unless($graph instanceof stdClass, LogicException::class, 'Expected the prepared graph fixture.');
        $graphData = get_object_vars($graph);
        Arr::set($graphData, 'containers.1.widgets.0.data.sections', [['html' => $placeholder]]);
        $context->publicRenderData->layoutGraph = (object) $graphData;
    }

    // The cache contains public data only; a new request has neither loader state nor stored widgets.
    $cached = unserialize(serialize($context->publicRenderData));
    throw_unless($cached instanceof PublicPageRenderData, LogicException::class, 'Expected cached public render data.');
    $context->publicRenderData = $cached;
    app()->forgetInstance(LayoutLoader::class);
    CapellLayoutManager::clearContainerWidgets();

    $guardedQueries = [];
    $versionQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$guardedQueries, &$versionQueries): void {
        if (resolve(PublicViewQueryGuard::class)->isActive()) {
            $guardedQueries[] = $query->sql;
        }

        if (str_contains($query->sql, 'from "widgets"') && str_contains($query->sql, '"widgets"."id"')) {
            $versionQueries++;
        }
    });

    $html = renderPreparedLayout($context);
    $dom = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    $fragmentUrl = $dom->querySelector('[data-deferred-fragment]')?->getAttribute('data-deferred-fragment-url');
    $codec = resolve(PublicFragmentReferenceCodec::class);
    expect($dom->querySelectorAll('[data-deferred-fragment]')->length)->toBe(1)
        ->and($codec->decode(rawurldecode(basename((string) $fragmentUrl))))->toEqual($codec->decode((string) $reference))
        ->and($dom->querySelectorAll('[data-fallback]')->length)->toBe(0)
        ->and($dom->querySelectorAll('[data-prepared]')->length)->toBe(3)
        ->and($guardedQueries)->toBe([])
        ->and($versionQueries)->toBe($preparedPlaceholder ? 0 : 1);
    if ($preparedPlaceholder) {
        expect(substr_count($html, $placeholder))->toBe(1)
            ->and($fragmentUrl)->toBe(url('/_fragments/' . rawurlencode((string) $reference)));
    }
})->with(['unprepared lazy widget' => false, 'prepared lazy placeholder' => true]);

it('reuses a prepared lazy placeholder in another layout area without rebuilding its reference', function (): void {
    [$context, $widget] = preparedLayoutFixture();
    assert($context->layout instanceof Layout && $context->publicRenderData instanceof PublicPageRenderData);
    $context->layout->meta = ['layout_file' => 'lb-prepared::with-area'];
    $containers = $context->layout->containers ?? [];
    $containers['header'] = [
        'meta' => ['area' => 'header', 'html_class' => 'header-shell'],
        'widgets' => [[
            'widget_key' => $widget->key,
            'occurrence' => 3,
            'meta' => ['presentation' => ['delivery_mode' => PresentationDeliveryMode::LazyFragment->value]],
        ]],
    ];
    $context->layout->containers = $containers;
    $reference = BuildLayoutBuilderFragmentReferenceAction::run('header', 3, $widget);
    $placeholder = '<div id="prepared-header" data-deferred-fragment data-deferred-fragment-url="'
        . e(url('/_fragments/' . rawurlencode((string) $reference))) . '"></div>';
    $graph = $context->publicRenderData->layoutGraph;
    throw_unless($graph instanceof stdClass, LogicException::class, 'Expected the prepared graph fixture.');
    $graphData = get_object_vars($graph);
    Arr::set($graphData, 'containers.2', ['key' => 'header', 'widgets' => [[
        'key' => $widget->key, 'occurrence' => 3, 'data' => ['sections' => [['html' => $placeholder]]],
    ]]]);
    $context->publicRenderData->layoutGraph = (object) $graphData;
    Frontend::shouldReceive('getFrontendData')->with('publicPageRenderData')->andReturn($context->publicRenderData);

    $html = renderPreparedLayout($context);
    $dom = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    expect($dom->querySelectorAll('#layout-container-header.header-shell #prepared-header')->length)->toBe(1)
        ->and($dom->querySelectorAll('[data-deferred-fragment]')->length)->toBe(1)
        ->and(substr_count($html, $placeholder))->toBe(1);
});

it('preserves fully unhydrated layout output with and without a public graph', function (bool $graph, bool $foundation): void {
    [$context] = preparedLayoutFixture(foundation: $foundation);
    assert($context->publicRenderData instanceof PublicPageRenderData);
    $context->publicRenderData->layoutGraph = $graph ? (object) ['containers' => []] : null;
    resolve(LayoutLoaded::class)->handle('loadedLayout', new stdClass);

    $dom = HTMLDocument::createFromString(renderPreparedLayout($context), LIBXML_NOERROR);
    expect($dom->querySelectorAll('[data-fallback]')->length)->toBe(3)
        ->and($dom->querySelectorAll('[data-prepared]')->length)->toBe(0)
        ->and($dom->querySelectorAll('#layout-container-main.review-shell')->length)->toBe(1)
        ->and($dom->querySelectorAll('#layout-container-sibling.sibling-shell')->length)->toBe(1);
})->with([false, true])->with(['Core layout' => false, 'Foundation layout' => true]);

it('preserves fully hydrated contributor output and the page introduction on the partial layout path', function (bool $partial): void {
    [$context, $first] = preparedLayoutFixture(presentation: false, foundation: true);
    assert($context->layout instanceof Layout && $context->page instanceof Page && $context->language instanceof Language);
    assert($context->publicRenderData instanceof PublicPageRenderData);
    $first->update(['key' => PreparedSectionPayloadContributor::INTRODUCTION_WIDGET_KEY]);
    $second = Widget::factory()->create([
        'key' => PreparedSectionPayloadContributor::KEY_PREFIX . 'rich-text',
        'component' => 'prepared-test-widget',
    ]);
    $context->page->translation?->setAttribute('content', '<p>Page introduction fixture</p>');
    $containers = [
        'main' => [
            'meta' => $partial ? ['html_class' => 'partial-shell'] : ['landmark' => 'main'],
            'widgets' => [['widget_key' => $first->key, 'occurrence' => 1]],
        ],
        'sibling' => ['widgets' => [['widget_key' => $second->key, 'occurrence' => 1]]],
    ];
    if ($partial) {
        $fallback = Widget::factory()->create(['key' => 'third-party', 'component' => 'prepared-test-widget']);
        $containers['sibling']['widgets'][] = ['widget_key' => $fallback->key, 'occurrence' => 1];
    }

    $context->layout->containers = $containers;
    app()->tag([PreparedSectionPayloadContributor::class], PublicLayoutWidgetPayloadContributor::TAG);
    $context->publicRenderData->layoutGraph = BuildPublicLayoutGraphAction::run($context->layout, $context->page, $context->language);

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        if (resolve(PublicViewQueryGuard::class)->isActive()) {
            $queries[] = $query->sql;
        }
    });
    $html = renderPreparedLayout($context);
    $dom = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    expect(substr_count($html, 'Page introduction fixture'))->toBe(1)
        ->and($dom->querySelectorAll('.prepared-section-reference')->length)->toBe(2)
        ->and($dom->querySelectorAll('[data-fallback]')->length)->toBe($partial ? 1 : 0)
        ->and($dom->querySelectorAll('#layout-container-main.partial-shell')->length)->toBe($partial ? 1 : 0)
        ->and($queries)->toBe([]);
    foreach (['admin_schema', 'signed_url', 'data-capell-authoring', 'data-field-path'] as $marker) {
        expect($html)->not->toContain($marker);
    }
})->with(['fully hydrated' => false, 'partly hydrated' => true]);

it('continues rejecting third-party widgets that query while rendering', function (): void {
    [$context] = preparedLayoutFixture();
    assert($context->layout instanceof Layout);
    $containers = $context->layout->containers ?? [];
    Arr::set($containers, 'sibling.widgets.0.meta.query_during_render', true);
    $context->layout->containers = $containers;

    resolve(LayoutLoaded::class)->handle('loadedLayout', new stdClass);
    expect(fn (): string => renderPreparedLayout($context))->toThrow(RuntimeException::class, 'Public Blade rendering executed 1 database query');
});

it('matches prepared contexts by widget key and occurrence rather than the caller loop index', function (): void {
    [$context, $widget] = preparedLayoutFixture(presentation: false);
    resolve(PublicLayoutRenderPreparer::class)->prepare($context);
    $resolve = static fn (Widget $widget, array $widgetData, int $widgetIndex): mixed => PublicRenderDataScope::during(
        $context->publicRenderData,
        static fn () => ResolvePublicWidgetRenderContextAction::run(
            layout: $context->layout,
            containerKey: 'main',
            widgetIndex: $widgetIndex,
            widget: $widget,
            widgetData: $widgetData,
            type: 'blade',
        ),
    );

    $second = ['widget_key' => $widget->key, 'occurrence' => 2];
    $atOwnIndex = $resolve($widget, $second, 1);
    $reindexed = $resolve($widget, $second, 0);
    $first = $resolve($widget, ['widget_key' => $widget->key, 'occurrence' => 1], 0);

    expect($reindexed->occurrence)->toBe(2)
        ->and($reindexed->presentation)->toEqual($atOwnIndex->presentation)
        ->and($reindexed->resourcePublicIds)->toBe($atOwnIndex->resourcePublicIds)
        ->and($reindexed->widgetDomId)->toBe($first->widgetDomId)
        ->and($reindexed->widgetDomId)->not->toBe($atOwnIndex->widgetDomId);

    $other = Widget::factory()->create(['key' => 'review-other-widget', 'component' => 'prepared-test-widget']);
    $unprepared = $resolve($other, ['widget_key' => $other->key, 'occurrence' => 1], 0);

    expect($unprepared->occurrence)->toBe(1)
        ->and($unprepared->widgetReference)->toBeNull()
        ->and($unprepared)->not->toBe($first);
});

it('keeps prepared state with its own render and releases it afterwards', function (): void {
    [$outer] = preparedLayoutFixture(presentation: false);
    $outerData = $outer->publicRenderData;
    throw_unless($outerData instanceof PublicPageRenderData, RuntimeException::class);
    $innerData = clone $outerData;
    $inner = new FrontendRenderContextData(
        page: $outer->page,
        site: $outer->site,
        language: $outer->language,
        layout: $outer->layout,
        theme: $outer->theme,
        runtimeManifest: $outer->runtimeManifest,
        publicRenderData: $innerData,
    );
    $preparer = resolve(PublicLayoutRenderPreparer::class);
    throw_unless($preparer instanceof LayoutBuilderPublicLayoutRenderPreparer, RuntimeException::class);

    $preparer->prepare($outer);
    PublicRenderDataScope::during($outerData, function () use ($preparer, $inner, $innerData, $outer, $outerData): void {
        $preparer->prepare($inner);
        PublicRenderDataScope::during($innerData, static function () use ($preparer, $outer, $innerData): void {
            expect($preparer->publicRenderData($outer->layout))->toBe($innerData);
        });

        expect($preparer->publicRenderData($outer->layout))->toBe($outerData)
            ->and($preparer->context($outer->layout, 'main', 'prepared-probe', 1))->not->toBeNull();
    });

    expect(PublicRenderDataScope::current())->toBeNull()
        ->and($preparer->publicRenderData($outer->layout))->toBeNull()
        ->and($preparer->context($outer->layout, 'main', 'prepared-probe', 1))->toBeNull();
});
