<?php

declare(strict_types=1);

use Capell\ThemeStudio\Core\Actions\RenderThemePageAction;
use Capell\ThemeStudio\Core\Contracts\ThemeRenderer;
use Capell\ThemeStudio\Core\Contracts\ThemeSection;
use Capell\ThemeStudio\Core\Data\BrandProfileData;
use Capell\ThemeStudio\Core\Data\HeroSectionData;
use Capell\ThemeStudio\Core\Data\ThemeDefinitionData;
use Capell\ThemeStudio\Core\Data\ThemePageData;
use Capell\ThemeStudio\Core\Data\ThemePresetData;
use Capell\ThemeStudio\Core\Exceptions\ThemeNotFoundException;
use Capell\ThemeStudio\Core\Http\Middleware\ResolveThemePreviewContext;
use Capell\ThemeStudio\Core\Preview\ThemePreviewContext;
use Capell\ThemeStudio\Core\Rendering\BladeThemeRenderer;
use Capell\ThemeStudio\Core\Rendering\ViewSectionRenderer;
use Capell\ThemeStudio\Core\Theme\ThemeRegistry;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;

it('registers full theme definitions and renderer maps', function (): void {
    $registry = new ThemeRegistry;
    $sectionRenderer = new ViewSectionRenderer('corporate', 'hero', 'missing-view');
    $themeRenderer = new BladeThemeRenderer('corporate', 'missing-layout', ['hero' => $sectionRenderer]);

    $registry->register(
        new ThemeDefinitionData(
            key: 'corporate',
            name: 'Corporate',
            description: 'Trust-led',
            package: 'capell-app/theme-corporate',
            previewImage: '/preview.jpg',
            tags: ['Trust'],
            bestFit: ['B2B'],
            includedSections: ['hero'],
            presets: [],
        ),
        $themeRenderer,
        [$sectionRenderer],
    );

    expect($registry->has('corporate'))->toBeTrue()
        ->and($registry->definition('corporate')->package)->toBe('capell-app/theme-corporate')
        ->and($registry->renderer('corporate'))->toBe($themeRenderer)
        ->and($registry->sectionRenderer('corporate', 'hero'))->toBe($sectionRenderer);
});

it('throws for missing themes', function (): void {
    expect(fn (): mixed => (new ThemeRegistry)->definition('missing'))
        ->toThrow(ThemeNotFoundException::class);
});

it('renders sections through fallback renderers when a specialized section is unsupported', function (): void {
    $fallbackRenderer = new ViewSectionRenderer('corporate', 'content-listing', 'missing-view');
    $renderer = new BladeThemeRenderer('corporate', 'missing-layout', ['content-listing' => $fallbackRenderer]);

    $section = new class implements ThemeSection
    {
        public function key(): string
        {
            return 'pricing';
        }

        public function fallbackKey(): string
        {
            return 'content-listing';
        }

        public function toViewData(): array
        {
            return ['section' => $this];
        }
    };

    $html = $renderer->render(new ThemePageData(
        title: 'Example',
        brand: new BrandProfileData,
        sections: [$section],
    ));

    expect($html)->toContain('data-theme="corporate"')
        ->and($html)->toContain('data-section="pricing"');
});

it('throws renderer failures when a first party renderer is marked as loud', function (): void {
    $renderer = new ViewSectionRenderer('corporate', 'hero', 'missing-view', failLoudly: true);

    expect(fn (): string => $renderer->render(new HeroSectionData(heading: 'Broken view')))
        ->toThrow(InvalidArgumentException::class);
});

it('keeps shared section data portable', function (): void {
    $section = new HeroSectionData(
        heading: 'Same content, different theme',
        actions: [['label' => 'Preview', 'url' => '/preview']],
    );

    expect($section->key())->toBe('hero')
        ->and($section->toViewData()['section'])->toBe($section);
});

it('registers the whole-site preview middleware on the web group', function (): void {
    $webMiddleware = resolve(Router::class)->getMiddlewareGroups()['web'] ?? [];

    expect($webMiddleware)->toContain(ResolveThemePreviewContext::class);
});

it('renders pages through the preview theme and preset when preview context is present', function (): void {
    $registry = resolve(ThemeRegistry::class);
    $registry->reset();

    $registry->register(
        new ThemeDefinitionData(
            key: 'corporate',
            name: 'Corporate',
            description: 'Trust-led',
            package: 'capell-app/theme-corporate',
            previewImage: '/corporate.jpg',
            tags: ['Trust'],
            bestFit: ['B2B'],
            includedSections: ['hero'],
            presets: [
                new ThemePresetData(
                    key: 'boardroom',
                    name: 'Boardroom',
                    description: 'Formal',
                    previewImage: '/corporate.jpg',
                    values: ['primaryColor' => '#111111'],
                ),
            ],
        ),
        new class implements ThemeRenderer
        {
            public function themeKey(): string
            {
                return 'corporate';
            }

            public function render(ThemePageData $page): string
            {
                return 'corporate:' . $page->brand->primaryColor;
            }
        },
        [],
    );

    $registry->register(
        new ThemeDefinitionData(
            key: 'agency',
            name: 'Agency',
            description: 'Expressive',
            package: 'capell-app/theme-agency',
            previewImage: '/agency.jpg',
            tags: ['Expressive'],
            bestFit: ['Studios'],
            includedSections: ['hero'],
            presets: [
                new ThemePresetData(
                    key: 'signal',
                    name: 'Signal',
                    description: 'Bold',
                    previewImage: '/agency.jpg',
                    values: ['primaryColor' => '#222222'],
                ),
            ],
        ),
        new class implements ThemeRenderer
        {
            public function themeKey(): string
            {
                return 'agency';
            }

            public function render(ThemePageData $page): string
            {
                return 'agency:' . $page->brand->primaryColor;
            }
        },
        [],
    );

    $html = RenderThemePageAction::run(
        page: new ThemePageData(title: 'Preview', brand: new BrandProfileData),
        activeTheme: 'corporate',
        activePreset: 'boardroom',
        previewContext: new ThemePreviewContext(
            themeKey: 'agency',
            presetKey: 'signal',
            previewing: true,
        ),
    );

    expect($html)->toBe('agency:#222222');
});

it('renders shared hero media instead of forcing placeholder artwork', function (): void {
    $template = (string) file_get_contents(__DIR__ . '/../../../themes/corporate/resources/views/sections/hero.blade.php');

    $html = Blade::render($template, [
        'section' => new HeroSectionData(
            heading: 'Portable media',
            mediaUrl: '/hero.jpg',
            mediaAlt: 'Hero preview',
        ),
    ]);

    expect($html)->toContain('src="/hero.jpg"')
        ->and($html)->toContain('alt="Hero preview"');
});
