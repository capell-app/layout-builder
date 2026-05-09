<?php

declare(strict_types=1);

use Capell\Core\Enums\FrontendRuntime;
use Capell\Core\ThemeStudio\Contracts\ThemeSection;
use Capell\Core\ThemeStudio\Data\BrandProfileData;
use Capell\Core\ThemeStudio\Data\HeroSectionData;
use Capell\Core\ThemeStudio\Data\ThemeDefinitionData;
use Capell\Core\ThemeStudio\Data\ThemePageData;
use Capell\Core\ThemeStudio\Exceptions\ThemeNotFoundException;
use Capell\Core\ThemeStudio\Rendering\BladeThemeRenderer;
use Capell\Core\ThemeStudio\Rendering\ViewSectionRenderer;
use Capell\Core\ThemeStudio\Theme\ThemeRegistry;

it('registers theme definitions renderers and section renderers by theme key', function (): void {
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

it('throws for missing registered themes', function (): void {
    expect(fn (): mixed => (new ThemeRegistry)->definition('missing'))
        ->toThrow(ThemeNotFoundException::class);
});

it('renders fallback section keys without coupling content to one renderer package', function (): void {
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

it('throws renderer failures when a first-party section renderer is marked loud', function (): void {
    $renderer = new ViewSectionRenderer('corporate', 'hero', 'missing-view', failLoudly: true);

    expect(fn (): string => $renderer->render(new HeroSectionData(heading: 'Broken view')))
        ->toThrow(InvalidArgumentException::class);
});

it('keeps shared section data portable across theme packages', function (): void {
    $section = new HeroSectionData(
        heading: 'Same content, different theme',
        actions: [['label' => 'Preview', 'url' => '/preview']],
    );

    expect($section->key())->toBe('hero')
        ->and($section->toViewData()['section'])->toBe($section);
});

it('stores runtime metadata with theme definitions', function (): void {
    $definition = new ThemeDefinitionData(
        key: 'inertia-test',
        name: 'Inertia Test',
        description: 'Runtime-aware test theme.',
        package: 'capell-app/theme-inertia-test',
        previewImage: '/preview.jpg',
        tags: [],
        bestFit: [],
        includedSections: [],
        presets: [],
        runtime: FrontendRuntime::Inertia,
        frontend: ['entry' => 'resources/js/app.ts'],
    );

    expect($definition->runtime->value)->toBe('inertia')
        ->and($definition->frontend)->toBe(['entry' => 'resources/js/app.ts']);
});
