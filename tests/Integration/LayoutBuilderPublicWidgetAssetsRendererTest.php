<?php

declare(strict_types=1);

use Capell\Core\Data\RenderableDefinitionData;
use Capell\Core\Enums\RenderableTypeEnum;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Translation;
use Capell\Core\Support\Renderables\RenderableRegistry;
use Capell\Frontend\Contracts\Fragments\PublicFragmentUrlResolver;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Data\Fragments\PublicFragmentReferenceData;
use Capell\Frontend\Support\Fragments\PublicFragmentUrlResolverRegistry;
use Capell\Frontend\Support\Renderables\RenderableDynamicDataRegistry;
use Capell\LayoutBuilder\Actions\ResolvePublicWidgetAssetsAction;
use Capell\LayoutBuilder\Contracts\Assets\PublicLayoutWidgetAssetsRenderer;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

beforeEach(function (): void {
    Model::preventAccessingMissingAttributes(false);

    $viewPath = storage_path('framework/testing/layout-builder-public-widget-assets-renderer');

    File::ensureDirectoryExists($viewPath);
    File::put($viewPath . '/asset.blade.php', '<article data-render-key="{{ $renderKey }}">{{ $translation->title }} {{ $dynamicData["badge"] ?? "" }}</article>');

    View::addNamespace('layout-builder-renderer-test', $viewPath);

    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(
        key: 'feature',
        type: RenderableTypeEnum::Section,
        blade: 'layout-builder-renderer-test::asset',
    ));

    resolve(RenderableRegistry::class)->register(new RenderableDefinitionData(
        key: 'section',
        type: RenderableTypeEnum::Section,
        blade: 'layout-builder-renderer-test::asset',
    ));
});

it('renders explicitly grouped widget assets without querying frontend context', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();
    $asset = layoutBuilderRendererWidgetAsset($language, 'Grouped Asset', ['kind' => 'feature']);
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->container('main')
        ->occurrence(2)
        ->create(['order' => 10]);
    $widgetAsset->setRelation('asset', $asset);

    $html = resolve(PublicLayoutWidgetAssetsRenderer::class)->render(
        widget: $widget,
        containerKey: 'main',
        widgetData: ['occurrence' => 2],
        widgetAssetsByWidget: collect([
            $widget->getKey() . ':main:2' => collect([$widgetAsset]),
        ]),
    );

    expect($html)->toContain('Grouped Asset')
        ->and($html)->toContain('data-render-key="feature"');
});

it('falls back to frontend context and filters non-public translations', function (): void {
    $language = Language::factory()->create();
    $otherLanguage = Language::factory()->create();
    $site = Site::factory()->create(['language_id' => $language->getKey()]);
    $layout = Layout::factory()->site($site)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widget = Widget::factory()->create();
    $publicAsset = layoutBuilderRendererWidgetAsset($language, 'Public Asset', ['kind' => 'feature']);
    $wrongLanguageAsset = layoutBuilderRendererWidgetAsset($otherLanguage, 'Wrong Language Asset', ['kind' => 'feature']);

    WidgetAsset::factory()
        ->widget($widget)
        ->asset($publicAsset)
        ->page($page, 'main', 3)
        ->create(['order' => 10]);
    WidgetAsset::factory()
        ->widget($widget)
        ->asset($wrongLanguageAsset)
        ->page($page, 'main', 3)
        ->create(['order' => 20]);

    app()->instance(FrontendContextReader::class, layoutBuilderRendererContext($page, $site, $language, $layout));

    $html = resolve(PublicLayoutWidgetAssetsRenderer::class)->render(
        widget: $widget,
        containerKey: 'main',
        widgetData: ['occurrence' => 3],
    );

    expect($html)->toContain('Public Asset')
        ->and($html)->not->toContain('Wrong Language Asset');
});

it('eager loads translations from a preloaded base asset collection', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $layout = Layout::factory()->site($site)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widget = Widget::factory()->create();
    $asset = layoutBuilderRendererWidgetAsset($language, 'Preloaded Asset', ['kind' => 'feature']);
    $asset->unsetRelation('translation');
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->container('main')
        ->occurrence(1)
        ->create(['workspace_id' => 0]);
    $widgetAsset->setRelation('asset', $asset);
    $widget->setRelation('assets', collect([$widgetAsset]));

    $assets = ResolvePublicWidgetAssetsAction::run(
        $widget,
        $page,
        $language,
        'main',
        1,
    );

    expect($assets)->toHaveCount(1)
        ->and($assets->first()?->asset?->relationLoaded('translation'))->toBeTrue()
        ->and($assets->first()?->asset?->translation?->title)->toBe('Preloaded Asset');
});

it('preserves relations already eager loaded on public widget assets', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $layout = Layout::factory()->site($site)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widget = Widget::factory()->create();
    $asset = layoutBuilderRendererWidgetAsset($language, 'Preloaded Relations', ['kind' => 'feature']);
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->container('main')
        ->occurrence(1)
        ->create(['workspace_id' => 0]);
    $widgetAsset->setRelation('asset', $asset);
    $widget->setRelation('assets', collect([$widgetAsset]));
    $queryCount = 0;

    DB::listen(function () use (&$queryCount): void {
        $queryCount++;
    });

    $assets = ResolvePublicWidgetAssetsAction::run(
        $widget,
        $page,
        $language,
        'main',
        1,
    );

    expect($assets)->toHaveCount(1)
        ->and($assets->first()?->asset)->toBe($asset)
        ->and($queryCount)->toBe(0);
});

it('memoizes public asset translation schema checks for the request', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $layout = Layout::factory()->site($site)->create();
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widget = Widget::factory()->create();
    $asset = layoutBuilderRendererWidgetAsset($language, 'Memoized Asset', ['kind' => 'feature']);
    $asset->unsetRelation('translation');
    $widgetAsset = WidgetAsset::factory()
        ->widget($widget)
        ->asset($asset)
        ->container('main')
        ->occurrence(1)
        ->create(['workspace_id' => 0]);
    $widgetAsset->setRelation('asset', $asset);
    $widget->setRelation('assets', collect([$widgetAsset]));
    $schemaQueryCount = 0;

    DB::listen(function (QueryExecuted $query) use (&$schemaQueryCount): void {
        if (str_contains($query->sql, 'information_schema.columns')) {
            $schemaQueryCount++;
        }
    });

    ResolvePublicWidgetAssetsAction::run($widget, $page, $language, 'main', 1);
    $asset->unsetRelation('translation');
    ResolvePublicWidgetAssetsAction::run($widget, $page, $language, 'main', 1);

    expect($schemaQueryCount)->toBe(DB::connection()->getDriverName() === 'sqlite' ? 0 : 2);
});

it('renders deferred placeholders before renderable dispatch', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $layout = Layout::factory()->site($site)->create(['status' => true]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widget = Widget::factory()->create();
    $asset = layoutBuilderRendererWidgetAsset($language, 'Deferred Asset', [
        'kind' => 'feature',
        'performance' => [
            'defer' => true,
            'fragment_owner' => 'test-owner',
            'defer_strategy' => 'idle',
            'defer_min_height' => '12rem',
        ],
    ]);
    $widgetAsset = WidgetAsset::factory()->widget($widget)->asset($asset)->create();
    $widgetAsset->setRelation('asset', $asset);

    app()->instance(FrontendContextReader::class, layoutBuilderRendererContext($page, $site, $language, $layout));
    registerLayoutBuilderRendererDeferredOwner();

    $html = resolve(PublicLayoutWidgetAssetsRenderer::class)->render(
        widget: $widget,
        containerKey: 'main',
        widgetAssets: collect([$widgetAsset]),
    );
    $secondHtml = resolve(PublicLayoutWidgetAssetsRenderer::class)->render(
        widget: $widget,
        containerKey: 'main',
        widgetAssets: collect([$widgetAsset]),
    );

    $assetKey = $asset->getKey();
    $assetIdentifier = is_scalar($assetKey) ? (string) $assetKey : '';
    preg_match('/data-deferred-fragment-key="([a-f0-9]{64})"/', $html, $firstCacheKey);
    preg_match('/data-deferred-fragment-key="([a-f0-9]{64})"/', $secondHtml, $secondCacheKey);

    expect($html)->toContain('data-deferred-fragment')
        ->and($html)->toContain('data-deferred-fragment-url="/deferred-fragments/' . $assetIdentifier . '"')
        ->and($html)->toContain('data-deferred-fragment-strategy="idle"')
        ->and($html)->not->toContain('Deferred Asset')
        ->and($firstCacheKey[1] ?? null)->not->toBeNull()
        ->and($secondCacheKey[1] ?? null)->toBe($firstCacheKey[1] ?? null);
});

it('does not requery hydrated assets or shared context for each deferred placeholder', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $layout = Layout::factory()->site($site)->create(['status' => true]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widget = Widget::factory()->create();
    $widgetAssets = collect(range(1, 3))->map(function (int $index) use ($language, $widget): WidgetAsset {
        $asset = layoutBuilderRendererWidgetAsset($language, 'Deferred Asset ' . $index, [
            'kind' => 'feature',
            'performance' => [
                'defer' => true,
                'fragment_owner' => 'test-owner',
            ],
        ]);
        $widgetAsset = WidgetAsset::factory()->widget($widget)->asset($asset)->create();
        $widgetAsset->setRelation('asset', $asset);

        return $widgetAsset;
    });

    app()->instance(FrontendContextReader::class, layoutBuilderRendererContext($page, $site, $language, $layout));
    registerLayoutBuilderRendererDeferredOwner();

    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = ['sql' => $query->sql, 'bindings' => $query->bindings];
    });

    $html = resolve(PublicLayoutWidgetAssetsRenderer::class)->render(
        widget: $widget,
        containerKey: 'main',
        widgetAssets: $widgetAssets,
    );

    preg_match_all('/\sdata-deferred-fragment(?:\s|>)/', $html, $placeholders);
    $firstAsset = $widgetAssets->firstOrFail()->getRelationValue('asset');
    assert($firstAsset instanceof Model);
    $assetMorphClass = $firstAsset->getMorphClass();
    $assetQueries = collect($queries)->filter(
        static fn (array $query): bool => preg_match('/\bfrom\s+[`"]?widgets\b/i', $query['sql']) === 1,
    );
    $assetTranslationQueries = collect($queries)->filter(
        static fn (array $query): bool => str_contains($query['sql'], 'translations')
            && in_array($assetMorphClass, $query['bindings'], true),
    );

    expect($placeholders[0])->toHaveCount(3)
        ->and($assetQueries)->toBeEmpty()
        ->and($assetTranslationQueries)->toBeEmpty()
        ->and($queries)->toHaveCount(6);
});

it('changes deferred cache identities when hydrated asset or translation data changes', function (): void {
    $language = Language::factory()->create();
    $site = Site::factory()->language($language)->withTranslations($language)->create();
    $layout = Layout::factory()->site($site)->create(['status' => true]);
    $page = Page::factory()->site($site)->layout($layout)->withTranslations($language)->create();
    $widget = Widget::factory()->create();
    $asset = layoutBuilderRendererWidgetAsset($language, 'Deferred Asset', [
        'kind' => 'feature',
        'performance' => [
            'defer' => true,
            'fragment_owner' => 'test-owner',
        ],
    ]);
    $widgetAsset = WidgetAsset::factory()->widget($widget)->asset($asset)->create();
    $widgetAsset->setRelation('asset', $asset);

    app()->instance(FrontendContextReader::class, layoutBuilderRendererContext($page, $site, $language, $layout));
    registerLayoutBuilderRendererDeferredOwner();

    $renderer = resolve(PublicLayoutWidgetAssetsRenderer::class);
    $firstHtml = $renderer->render(
        widget: $widget,
        containerKey: 'main',
        widgetAssets: collect([$widgetAsset]),
    );

    $meta = $asset->getAttribute('meta');
    assert(is_array($meta));
    $asset->forceFill(['meta' => [...$meta, 'fragment_revision' => 'asset-changed']])->save();
    $freshAsset = $asset->fresh();
    assert($freshAsset instanceof Widget);
    $asset = $freshAsset->load('translation');
    $widgetAsset->setRelation('asset', $asset);

    $assetChangedHtml = $renderer->render(
        widget: $widget,
        containerKey: 'main',
        widgetAssets: collect([$widgetAsset]),
    );

    $translation = $asset->getRelationValue('translation');
    assert($translation instanceof Model);
    $translation->update(['title' => 'Changed Deferred Asset']);
    $freshAsset = $asset->fresh();
    assert($freshAsset instanceof Widget);
    $asset = $freshAsset->load('translation');
    $widgetAsset->setRelation('asset', $asset);

    $translationChangedHtml = $renderer->render(
        widget: $widget,
        containerKey: 'main',
        widgetAssets: collect([$widgetAsset]),
    );

    preg_match('/data-deferred-fragment-key="([a-f0-9]{64})"/', $firstHtml, $firstCacheKey);
    preg_match('/data-deferred-fragment-key="([a-f0-9]{64})"/', $assetChangedHtml, $assetChangedCacheKey);
    preg_match('/data-deferred-fragment-key="([a-f0-9]{64})"/', $translationChangedHtml, $translationChangedCacheKey);

    expect($firstCacheKey[1] ?? null)->not->toBeNull()
        ->and($assetChangedCacheKey[1] ?? null)->not->toBe($firstCacheKey[1] ?? null)
        ->and($translationChangedCacheKey[1] ?? null)->not->toBe($assetChangedCacheKey[1] ?? null);
});

it('renders the asset normally when a deferred fragment has no public url', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();
    $asset = layoutBuilderRendererWidgetAsset($language, 'Deferred Asset', [
        'kind' => 'feature',
        'performance' => ['defer' => true, 'fragment_owner' => 'unregistered-owner'],
    ]);
    $widgetAsset = WidgetAsset::factory()->widget($widget)->asset($asset)->create();
    $widgetAsset->setRelation('asset', $asset);

    $html = resolve(PublicLayoutWidgetAssetsRenderer::class)->render(
        widget: $widget,
        containerKey: 'main',
        widgetAssets: collect([$widgetAsset]),
    );

    expect($html)->toContain('Deferred Asset')
        ->and($html)->not->toContain('data-deferred-fragment');
});

it('dispatches renderables with dynamic data and implementation options', function (): void {
    $language = Language::factory()->create();
    $widget = Widget::factory()->create();
    $asset = layoutBuilderRendererWidgetAsset($language, 'Dynamic Asset', ['kind' => 'feature']);
    $widgetAsset = WidgetAsset::factory()->widget($widget)->asset($asset)->create();
    $widgetAsset->setRelation('asset', $asset);

    resolve(RenderableDynamicDataRegistry::class)->register(
        RenderableTypeEnum::Section,
        'feature',
        static fn (Model $asset, Model $translation, array $meta, string $renderKey): array => ['badge' => 'Dynamic Badge'],
    );

    $bladeHtml = resolve(PublicLayoutWidgetAssetsRenderer::class)->render(
        widget: $widget,
        containerKey: 'main',
        widgetAssets: collect([$widgetAsset]),
    );

    expect($bladeHtml)->toContain('Dynamic Asset Dynamic Badge')
        ->and($bladeHtml)->toContain('data-render-key="feature"');
});

/**
 * @param  array<string, mixed>  $meta
 */
function layoutBuilderRendererWidgetAsset(Language $language, string $title, array $meta = []): Widget
{
    $asset = Widget::factory()->create(['meta' => $meta]);

    Translation::factory()
        ->translatable($asset)
        ->language($language)
        ->create(['title' => $title]);

    return $asset->refresh()->load('translation');
}

function registerLayoutBuilderRendererDeferredOwner(): void
{
    app()->instance(PublicFragmentUrlResolverRegistry::class, new PublicFragmentUrlResolverRegistry([
        new class implements PublicFragmentUrlResolver
        {
            public function owner(): string
            {
                return 'test-owner';
            }

            public function url(PublicFragmentReferenceData $reference): string
            {
                return '/deferred-fragments/' . $reference->ownerContext['assetId'];
            }
        },
    ]));
}

function layoutBuilderRendererContext(Page $page, Site $site, Language $language, Layout $layout): FrontendContextReader
{
    $theme = Theme::factory()->create();

    return new readonly class($page, $site, $language, $layout, $theme) implements FrontendContextReader
    {
        public function __construct(
            private Page $page,
            private Site $site,
            private Language $language,
            private Layout $layout,
            private Theme $theme,
        ) {}

        public function site(): Site
        {
            return $this->site;
        }

        public function language(): Language
        {
            return $this->language;
        }

        public function page(): Page
        {
            return $this->page;
        }

        public function layout(): Layout
        {
            return $this->layout;
        }

        public function theme(): Theme
        {
            return $this->theme;
        }

        /**
         * @return array<string, mixed>
         */
        public function params(): array
        {
            return [];
        }

        public function slug(): ?string
        {
            return null;
        }

        public function isError(): bool
        {
            return false;
        }

        public function setFrontendData(string $key, mixed $value): self
        {
            return $this;
        }

        public function getFrontendData(?string $key = null): mixed
        {
            return null;
        }
    };
}
