<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\Core\Models\Site;
use Capell\Core\Models\Theme;
use Capell\LayoutBuilder\Contracts\Extenders\LayoutContainerSchemaExtender;
use Capell\LayoutBuilder\Data\LayoutContainerSchemaContextData;
use Capell\LayoutBuilder\Filament\Components\Forms\BorderSelect;
use Capell\LayoutBuilder\Filament\Configurators\Layouts\DefaultLayoutContainerConfigurator;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderCoverageSchemaHarness;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

it('resolves layout container schema context from the active layout theme', function (): void {
    $theme = Theme::factory()->create([
        'key' => 'foundation',
        'active_key' => 'saas',
    ]);
    $layout = Layout::factory()->for($theme)->create();

    $context = LayoutContainerSchemaContextData::fromLayout($layout, 'hero');

    expect($context->layout?->is($layout))->toBeTrue()
        ->and($context->containerKey)->toBe('hero')
        ->and($context->themeKey)->toBe('foundation')
        ->and($context->siteId)->toBeNull();
});

it('falls back to the layout site theme when the layout has no direct theme', function (): void {
    $theme = Theme::factory()->create(['key' => 'corporate']);
    $site = Site::factory()->theme($theme)->create();
    $layout = Layout::factory()->site($site)->create(['theme_id' => null]);

    $context = LayoutContainerSchemaContextData::fromLayout($layout, 'main');

    expect($context->themeKey)->toBe('corporate')
        ->and($context->siteId)->toBe($site->getKey());
});

it('adds active theme layout container fields and excludes other theme fields', function (): void {
    $theme = Theme::factory()->create(['key' => 'saas']);
    $layout = Layout::factory()->for($theme)->create();

    app()->bind('layout-container-schema-extender.saas', fn (): LayoutContainerSchemaExtender => new class implements LayoutContainerSchemaExtender
    {
        public function themeKey(): string
        {
            return 'saas';
        }

        public function themeLabel(): string
        {
            return 'SaaS';
        }

        public function supports(LayoutContainerSchemaContextData $context): bool
        {
            return $context->containerKey === 'hero';
        }

        public function extendContainerComponents(Schema $schema, LayoutContainerSchemaContextData $context): array
        {
            return [
                TextInput::make('surface_tone')
                    ->label('Surface tone'),
            ];
        }
    });
    app()->bind('layout-container-schema-extender.restaurant', fn (): LayoutContainerSchemaExtender => new class implements LayoutContainerSchemaExtender
    {
        public function themeKey(): string
        {
            return 'restaurant';
        }

        public function themeLabel(): string
        {
            return 'Restaurant';
        }

        public function supports(LayoutContainerSchemaContextData $context): bool
        {
            return true;
        }

        public function extendContainerComponents(Schema $schema, LayoutContainerSchemaContextData $context): array
        {
            return [
                TextInput::make('menu_tone')
                    ->label('Menu tone'),
            ];
        }
    });
    app()->tag([
        'layout-container-schema-extender.saas',
        'layout-container-schema-extender.restaurant',
    ], LayoutContainerSchemaExtender::TAG);

    $components = (new DefaultLayoutContainerConfigurator)->make(
        Schema::make()->record($layout),
        LayoutContainerSchemaContextData::fromLayout($layout, 'hero'),
    );
    $flattened = layoutContainerSchemaExtenderFlattenComponents($components);
    $fieldNames = collect($flattened)
        ->filter(fn (mixed $component): bool => $component instanceof TextInput || $component instanceof BorderSelect)
        ->map(fn (TextInput|BorderSelect $component): string => $component->getName())
        ->values()
        ->all();
    $themeSections = collect($flattened)
        ->filter(fn (mixed $component): bool => $component instanceof Section)
        ->filter(fn (Section $section): bool => $section->getStatePath() === 'meta.theme_settings.saas')
        ->values();
    $sectionHeadings = collect($flattened)
        ->filter(fn (mixed $component): bool => $component instanceof Section)
        ->map(fn (Section $section): mixed => $section->getHeading())
        ->values()
        ->all();
    $themeHeaderActions = $themeSections->first()?->getHeaderActions() ?? [];

    expect($fieldNames)->toContain('border', 'surface_tone')
        ->and($fieldNames)->not->toContain('menu_tone')
        ->and($themeSections)->toHaveCount(1)
        ->and($sectionHeadings)->toContain(
            __('capell-layout-builder::generic.layout_and_appearance'),
            __('capell-layout-builder::generic.advanced'),
            __('capell-layout-builder::generic.theme_settings_heading', ['theme' => 'SaaS']),
        )
        ->and(collect($themeHeaderActions)
            ->filter(fn (mixed $action): bool => $action instanceof Action)
            ->map(fn (Action $action): ?string => $action->getName())
            ->filter(fn (?string $actionName): bool => $actionName !== null)
            ->all())
        ->toBe(['reset_theme_settings']);
});

it('does not add theme fields when the extender does not support the container context', function (): void {
    $theme = Theme::factory()->create(['key' => 'saas']);
    $layout = Layout::factory()->for($theme)->create();

    app()->bind('layout-container-schema-extender.unsupported-saas', fn (): LayoutContainerSchemaExtender => new class implements LayoutContainerSchemaExtender
    {
        public function themeKey(): string
        {
            return 'saas';
        }

        public function themeLabel(): string
        {
            return 'SaaS';
        }

        public function supports(LayoutContainerSchemaContextData $context): bool
        {
            return false;
        }

        public function extendContainerComponents(Schema $schema, LayoutContainerSchemaContextData $context): array
        {
            return [
                TextInput::make('hidden_theme_field'),
            ];
        }
    });
    app()->tag(['layout-container-schema-extender.unsupported-saas'], LayoutContainerSchemaExtender::TAG);

    $components = (new DefaultLayoutContainerConfigurator)->make(
        Schema::make()->record($layout),
        LayoutContainerSchemaContextData::fromLayout($layout, 'main'),
    );
    $fieldNames = collect(layoutContainerSchemaExtenderFlattenComponents($components))
        ->filter(fn (mixed $component): bool => $component instanceof TextInput)
        ->map(fn (TextInput $component): string => $component->getName())
        ->values()
        ->all();

    expect($fieldNames)->not->toContain('hidden_theme_field');
});

/**
 * @param  array<int, Htmlable>  $components
 * @return array<int, mixed>
 */
function layoutContainerSchemaExtenderFlattenComponents(array $components): array
{
    $mounted = Schema::make(new LayoutBuilderCoverageSchemaHarness)
        ->components($components)
        ->getComponents();

    return layoutContainerSchemaExtenderFlattenMountedComponents($mounted);
}

/**
 * @param  array<int, mixed>  $components
 * @return array<int, mixed>
 */
function layoutContainerSchemaExtenderFlattenMountedComponents(array $components): array
{
    $flattened = [];

    foreach ($components as $component) {
        $flattened[] = $component;

        if (! is_object($component) || ! method_exists($component, 'getChildSchemas')) {
            continue;
        }

        foreach ($component->getChildSchemas() as $childSchema) {
            if ($childSchema instanceof Schema) {
                array_push($flattened, ...layoutContainerSchemaExtenderFlattenMountedComponents($childSchema->getComponents()));
            }
        }
    }

    return $flattened;
}
