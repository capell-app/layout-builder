<?php

declare(strict_types=1);

use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Frontend\Contracts\FrontendContextReader;
use Capell\Frontend\Enums\RenderHookLocation;
use Capell\Frontend\Support\CapellFrontendContext;
use Capell\Frontend\Support\Render\RenderHookRegistry;
use Capell\Mosaic\Models\Section;
use Capell\Mosaic\Models\Widget;
use Capell\Mosaic\Models\WidgetAsset;
use Capell\Mosaic\Support\CapellLayoutManager;
use Capell\ThemeStudio\Core\Actions\RenderCurrentThemePageAction;
use Capell\ThemeStudio\Core\Adapters\CapellFrontendThemePageAdapter;
use Capell\ThemeStudio\Core\Contracts\ThemePageAdapter;
use Capell\ThemeStudio\Core\Contracts\ThemeRuntimeSettings;
use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Capell\ThemeStudio\Core\Data\FeatureSectionData;
use Capell\ThemeStudio\Core\Data\HeroSectionData;
use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Data\ThemePageData;
use Capell\ThemeStudio\Core\Data\ThemePresetData;
use Capell\ThemeStudio\Core\Preview\ThemePreviewContext;
use Capell\ThemeStudio\Core\Rendering\BladeThemeRenderer;
use Capell\ThemeStudio\Core\Rendering\ViewSectionRenderer;
use Capell\ThemeStudio\Core\Settings\ThemeStudioSettings;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;

function registerThemeStudioRuntimeTestTheme(string $themeKey, string $presetKey): void
{
    $sectionRenderers = [
        'hero' => new ViewSectionRenderer($themeKey, 'hero', 'theme-studio-test::hero'),
    ];

    resolve(ThemeRegistry::class)->register(
        definition: new ThemeDefinitionData(
            key: $themeKey,
            name: 'Test Theme ' . $themeKey,
            description: 'Runtime test theme',
            package: 'capell-app/theme-test-' . $themeKey,
            previewImage: '/theme-test.jpg',
            tags: [],
            bestFit: [],
            includedSections: ['hero'],
            presets: [
                new ThemePresetData(
                    key: $presetKey,
                    name: 'Default',
                    description: 'Default preset',
                    previewImage: '/theme-test-default.jpg',
                    values: [],
                ),
            ],
            assets: [],
        ),
        themeRenderer: new BladeThemeRenderer(
            themeKey: $themeKey,
            layoutView: 'theme-studio-test::page',
            sectionRenderers: $sectionRenderers,
        ),
        sectionRenderers: array_values($sectionRenderers),
    );
}

function bindThemeStudioRuntimeTestPage(string $activeTheme = 'test-theme', string $activePreset = 'default'): void
{
    app()->bind(ThemeRuntimeSettings::class, fn (): ThemeRuntimeSettings => new class($activeTheme, $activePreset) implements ThemeRuntimeSettings
    {
        public function __construct(
            private readonly string $activeTheme,
            private readonly string $activePreset,
        ) {}

        public function activeTheme(): string
        {
            return $this->activeTheme;
        }

        public function activePreset(): string
        {
            return $this->activePreset;
        }

        public function brandProfile(): BrandProfileData
        {
            return BrandProfileData::from();
        }

        public function themeOverrides(): array
        {
            return [];
        }
    });

    app()->bind(ThemePageAdapter::class, fn (): ThemePageAdapter => new class implements ThemePageAdapter
    {
        public function currentPage(): ThemePageData
        {
            return new ThemePageData(
                title: 'Portable page',
                brand: BrandProfileData::from(),
                sections: [
                    HeroSectionData::from([
                        'eyebrow' => 'Studio',
                        'heading' => 'Same content, premium rendering',
                        'summary' => 'The page content is portable across themes.',
                        'actions' => [
                            ['label' => 'Start', 'url' => '/'],
                        ],
                    ]),
                ],
            );
        }
    });
}

it('renders the current frontend page through the active theme runtime', function (): void {
    bindThemeStudioRuntimeTestPage();
    registerThemeStudioRuntimeTestTheme('test-theme', 'default');
    view()->addNamespace('theme-studio-test', __DIR__ . '/../Fixtures/views');

    $html = RenderCurrentThemePageAction::run();

    expect($html)->toContain('Same content, premium rendering');
});

it('binds theme runtime settings from the core package', function (): void {
    $settings = resolve(ThemeRuntimeSettings::class);

    expect($settings)->toBeInstanceOf(ThemeStudioSettings::class)
        ->and($settings->activeTheme())->toBe('corporate')
        ->and($settings->activePreset())->toBe('boardroom');
});

it('registers the theme token stylesheet on the head close render hook', function (): void {
    registerThemeStudioRuntimeTestTheme('corporate', 'boardroom');

    $output = resolve(RenderHookRegistry::class)->renderAll(RenderHookLocation::HeadClose);

    expect($output)
        ->toContain('<link rel="stylesheet" href="http://localhost/vendor/capell-theme-studio/tokens/')
        ->and($output)->toContain('.css">');
});

it('uses preview theme and preset without mutating published runtime settings', function (): void {
    bindThemeStudioRuntimeTestPage('corporate', 'boardroom');
    registerThemeStudioRuntimeTestTheme('corporate', 'boardroom');
    registerThemeStudioRuntimeTestTheme('saas', 'launchpad');
    view()->addNamespace('theme-studio-test', __DIR__ . '/../Fixtures/views');

    app()->instance(
        ThemePreviewContext::class,
        new ThemePreviewContext(
            themeKey: 'saas',
            presetKey: 'launchpad',
            previewing: true,
        ),
    );

    $html = RenderCurrentThemePageAction::run();
    $settings = resolve(ThemeRuntimeSettings::class);

    expect($html)->toContain('data-theme="saas"')
        ->and($settings->activeTheme())->toBe('corporate')
        ->and($settings->activePreset())->toBe('boardroom');
});

it('maps loaded Mosaic widgets and page-level assets into portable theme sections', function (): void {
    bindThemeStudioRuntimeTestPage();
    CapellLayoutManager::clearContainerWidgets();

    $language = new Language(['name' => 'English', 'code' => 'en']);
    $site = new Site(['name' => 'Acme']);
    $page = new Page(['name' => 'Services']);
    $page->setRelation('translation', new Translation([
        'title' => 'Services',
        'content' => '<p>Page body copy.</p>',
    ]));
    $layout = new Layout;
    $layout->containers = [
        'hero' => ['widgets' => [['widget_key' => 'hero-banner']]],
        'main' => ['widgets' => [['widget_key' => 'feature-list']]],
    ];

    $heroWidget = new Widget(['name' => 'Hero', 'key' => 'hero-banner']);
    $heroWidget->setRelation('type', new Type(['key' => 'hero']));
    $heroWidget->setRelation('translation', new Translation([
        'title' => 'Built around your content',
        'content' => '<p>Reusable layout, page-specific assets.</p>',
    ]));
    $heroWidget->setRelation('assets', collect());

    $feature = new Section(['name' => 'Editorial workflow']);
    $feature->setRelation('translation', new Translation([
        'title' => 'Editorial workflow',
        'content' => '<p>Draft, preview, approve, and publish.</p>',
    ]));
    $feature->setRelation('pageUrl', new PageUrl(['url' => '/workflow']));

    $featureAsset = new WidgetAsset(['container' => 'main', 'occurrence' => 1]);
    $featureAsset->setRelation('asset', $feature);

    $featureWidget = new Widget(['name' => 'Features', 'key' => 'feature-list']);
    $featureWidget->setRelation('type', new Type(['key' => 'feature-list']));
    $featureWidget->setRelation('translation', new Translation([
        'title' => 'CMS builder features',
        'content' => '<p>The layout can reuse widgets and override assets per page.</p>',
    ]));
    $featureWidget->setRelation('assets', collect([$featureAsset]));

    CapellLayoutManager::storeContainerWidget('hero', 'hero-banner', $heroWidget);
    CapellLayoutManager::storeContainerWidget('main', 'feature-list', $featureWidget);

    app()->instance(
        CapellFrontendContext::class,
        new CapellFrontendContext(new class($site, $language, $page, $layout) implements FrontendContextReader
        {
            public function __construct(
                private readonly Site $site,
                private readonly Language $language,
                private readonly Page $page,
                private readonly Layout $layout,
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

            public function theme(): ?Theme
            {
                return null;
            }

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
        }),
    );

    $themePage = (new CapellFrontendThemePageAdapter)->currentPage();

    expect($themePage->sections)->toHaveCount(2)
        ->and($themePage->sections[0])->toBeInstanceOf(HeroSectionData::class)
        ->and($themePage->sections[0]->heading)->toBe('Built around your content')
        ->and($themePage->sections[1])->toBeInstanceOf(FeatureSectionData::class)
        ->and($themePage->sections[1]->features[0]['title'])->toBe('Editorial workflow')
        ->and($themePage->sections[1]->features[0]['url'])->toBe('/workflow');

    CapellLayoutManager::clearContainerWidgets();
});
