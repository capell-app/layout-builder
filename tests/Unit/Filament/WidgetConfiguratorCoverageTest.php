<?php

declare(strict_types=1);

use Capell\Admin\Filament\Components\Forms\ComponentSelect;
use Capell\Admin\Filament\Components\Forms\ConfiguratorSelect;
use Capell\LayoutBuilder\Enums\FeatureListLayout;
use Capell\LayoutBuilder\Enums\ImageGalleryColumnCount;
use Capell\LayoutBuilder\Enums\ImageGalleryLayout;
use Capell\LayoutBuilder\Enums\ModernAccentColor;
use Capell\LayoutBuilder\Enums\ModernCardGridColumnCount;
use Capell\LayoutBuilder\Enums\ModernCardGridHoverEffect;
use Capell\LayoutBuilder\Enums\ModernCardGridVariant;
use Capell\LayoutBuilder\Enums\ModernCtaLayout;
use Capell\LayoutBuilder\Enums\ModernFeatureListAnimation;
use Capell\LayoutBuilder\Enums\ModernFeatureListLayout;
use Capell\LayoutBuilder\Enums\ModernGridColumnCount;
use Capell\LayoutBuilder\Enums\ModernHeroHeight;
use Capell\LayoutBuilder\Enums\ModernImageGalleryLayout;
use Capell\LayoutBuilder\Enums\ModernPricingBillingOption;
use Capell\LayoutBuilder\Enums\ModernProcessStepsLayout;
use Capell\LayoutBuilder\Enums\ModernStatsLayout;
use Capell\LayoutBuilder\Enums\ModernTestimonialsColumnCount;
use Capell\LayoutBuilder\Enums\ModernTestimonialsDisplayMode;
use Capell\LayoutBuilder\Enums\ModernTextAlignment;
use Capell\LayoutBuilder\Enums\WidgetBasicSpacingValue;
use Capell\LayoutBuilder\Enums\WidgetSizeValue;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\AdminSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\ComponentSection;
use Capell\LayoutBuilder\Filament\Configurators\Types\WidgetTypeConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\AssetsWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\CardGridWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\CarouselWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\CTASectionWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\DefaultWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\FeatureListWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\HeroBannerWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\HeroWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ImageGalleryWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernAlternatingContentConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernCardGridConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernCTASectionConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernFaqConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernFeatureListConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernHeroBannerConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernImageGalleryConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernPricingTableConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernProcessStepsConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernStatsSectionConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernTeamMembersConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernTestimonialsConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\NavigationWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\PageContentWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ResultsWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\SystemWidgetConfigurator;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderCoverageSchemaHarness;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

/**
 * @param  class-string  $configuratorClass
 */
it('builds widget configurator schemas for each supported form operation', function (
    string $configuratorClass,
    string $operation,
): void {
    $configurator = new $configuratorClass;

    if (! method_exists($configurator, 'make')) {
        throw new RuntimeException(sprintf('Expected %s to define a make method.', $configuratorClass));
    }

    $components = $configurator->make(Schema::make()->operation($operation));

    capell_expect($components)->not->toBeEmpty();
    capell_expect($components)->each->toBeInstanceOf(Component::class);
})->with([
    'default create' => [DefaultWidgetConfigurator::class, 'create'],
    'default create option' => [DefaultWidgetConfigurator::class, 'createOption'],
    'default edit option' => [DefaultWidgetConfigurator::class, 'editOption'],
    'assets create' => [AssetsWidgetConfigurator::class, 'create'],
    'assets edit option' => [AssetsWidgetConfigurator::class, 'editOption'],
    'carousel edit option' => [CarouselWidgetConfigurator::class, 'editOption'],
    'hero edit option' => [HeroWidgetConfigurator::class, 'editOption'],
    'navigation create option' => [NavigationWidgetConfigurator::class, 'createOption'],
    'navigation edit option' => [NavigationWidgetConfigurator::class, 'editOption'],
    'navigation form' => [NavigationWidgetConfigurator::class, 'edit'],
    'page content option' => [PageContentWidgetConfigurator::class, 'editOption'],
    'page content form' => [PageContentWidgetConfigurator::class, 'edit'],
    'results option' => [ResultsWidgetConfigurator::class, 'editOption'],
    'results form' => [ResultsWidgetConfigurator::class, 'edit'],
    'system option' => [SystemWidgetConfigurator::class, 'editOption'],
    'system form' => [SystemWidgetConfigurator::class, 'edit'],
    'hero banner option' => [HeroBannerWidgetConfigurator::class, 'editOption'],
    'card grid option' => [CardGridWidgetConfigurator::class, 'editOption'],
    'feature list option' => [FeatureListWidgetConfigurator::class, 'editOption'],
    'cta option' => [CTASectionWidgetConfigurator::class, 'editOption'],
    'image gallery option' => [ImageGalleryWidgetConfigurator::class, 'editOption'],
]);

it('exposes static modern widget configurator schemas and defaults', function (
    string $configuratorClass,
    string $expectedDefaultKey,
    mixed $expectedDefaultValue,
): void {
    $schema = $configuratorClass::getFormSchema();
    $defaults = $configuratorClass::getDefaults();

    capell_expect($schema)
        ->not->toBeEmpty()
        ->each->toBeInstanceOf(Component::class)
        ->and($defaults[$expectedDefaultKey])->toBe($expectedDefaultValue);
})->with([
    'alternating content' => [ModernAlternatingContentConfigurator::class, 'title', 'How It Works'],
    'card grid' => [ModernCardGridConfigurator::class, 'title', 'Featured Widgets'],
    'cta section' => [ModernCTASectionConfigurator::class, 'heading', 'Ready to Create Stunning Layouts?'],
    'faq' => [ModernFaqConfigurator::class, 'title', 'Frequently Asked Questions'],
    'feature list' => [ModernFeatureListConfigurator::class, 'title', 'Why Choose Our Platform'],
    'hero banner' => [ModernHeroBannerConfigurator::class, 'title', 'Welcome to Capell'],
    'image gallery' => [ModernImageGalleryConfigurator::class, 'title', 'Our Work'],
    'pricing table' => [ModernPricingTableConfigurator::class, 'title', 'Simple, Transparent Pricing'],
    'process steps' => [ModernProcessStepsConfigurator::class, 'title', 'Our Process'],
    'stats section' => [ModernStatsSectionConfigurator::class, 'title', 'By The Numbers'],
    'team members' => [ModernTeamMembersConfigurator::class, 'title', 'Our Team'],
    'testimonials' => [ModernTestimonialsConfigurator::class, 'title', 'What Customers Say'],
]);

/**
 * @param  class-string<BackedEnum>  $enumClass
 * @param  list<int|string>  $expectedValues
 */
it('backs persisted widget select options with labelled enums', function (string $enumClass, array $expectedValues): void {
    $cases = $enumClass::cases();

    expect(array_map(static fn (BackedEnum $case): int|string => $case->value, $cases))->toBe($expectedValues);

    foreach ($cases as $case) {
        expect($case)->toBeInstanceOf(HasLabel::class);

        if (! $case instanceof HasLabel) {
            throw new RuntimeException(sprintf('Expected %s to implement HasLabel.', $enumClass));
        }

        expect($case->getLabel())->not->toBe('');
    }
})->with([
    'feature list layout' => [FeatureListLayout::class, ['vertical', 'horizontal']],
    'image gallery columns' => [ImageGalleryColumnCount::class, [1, 2, 3, 4]],
    'image gallery layout' => [ImageGalleryLayout::class, ['grid', 'carousel']],
    'modern accent color' => [ModernAccentColor::class, ['primary', 'secondary', 'tertiary']],
    'modern card grid columns' => [ModernCardGridColumnCount::class, [2, 3, 4]],
    'modern card grid hover effect' => [ModernCardGridHoverEffect::class, ['scale', 'shadow', 'lift']],
    'modern card grid variant' => [ModernCardGridVariant::class, ['default', 'elevated', 'glass']],
    'modern cta layout' => [ModernCtaLayout::class, ['centered', 'split']],
    'modern feature list animation' => [ModernFeatureListAnimation::class, ['fade-in', 'slide-up', 'zoom', 'bounce']],
    'modern feature list layout' => [ModernFeatureListLayout::class, ['vertical', 'grid']],
    'modern grid columns' => [ModernGridColumnCount::class, ['2', '3', '4']],
    'modern hero height' => [ModernHeroHeight::class, ['sm', 'md', 'lg', 'xl']],
    'modern image gallery layout' => [ModernImageGalleryLayout::class, ['grid', 'masonry']],
    'modern pricing billing option' => [ModernPricingBillingOption::class, ['monthly', 'annual', 'both']],
    'modern process steps layout' => [ModernProcessStepsLayout::class, ['horizontal', 'vertical']],
    'modern stats layout' => [ModernStatsLayout::class, ['horizontal', 'vertical']],
    'modern testimonials columns' => [ModernTestimonialsColumnCount::class, ['1', '2', '3']],
    'modern testimonials display mode' => [ModernTestimonialsDisplayMode::class, ['grid', 'carousel']],
    'modern text alignment' => [ModernTextAlignment::class, ['left', 'center', 'right']],
    'widget basic spacing' => [WidgetBasicSpacingValue::class, ['none', 'sm', 'md', 'lg']],
    'widget size' => [WidgetSizeValue::class, ['sm', 'md', 'lg']],
]);

it('exposes create actions for widget admin configurator fields', function (): void {
    $selects = collect(layoutBuilderFlattenComponentTree(layoutBuilderMountedComponentTree(AdminSchema::make())))
        ->filter(fn (mixed $component): bool => $component instanceof ConfiguratorSelect)
        ->keyBy(fn (ConfiguratorSelect $component): string => $component->getName());

    expect($selects)->toHaveKeys([
        'configurator',
        'layout_widget_configurator',
    ]);

    foreach (['configurator', 'layout_widget_configurator'] as $name) {
        $select = $selects[$name] ?? null;
        expect($select)->toBeInstanceOf(ConfiguratorSelect::class)
            ->and($select?->getSuffixActions())->toHaveKey('createConfigurator');
    }
});

it('exposes create actions for widget type configurator fields', function (): void {
    $selects = collect(layoutBuilderFlattenComponentTree(layoutBuilderWidgetTypeAdminTabComponents()))
        ->filter(fn (mixed $component): bool => $component instanceof ConfiguratorSelect)
        ->keyBy(fn (ConfiguratorSelect $component): string => $component->getName());

    expect($selects)->toHaveKeys([
        'configurator',
        'layout_widget_configurator',
    ]);

    foreach (['configurator', 'layout_widget_configurator'] as $name) {
        $select = $selects[$name] ?? null;
        expect($select)->toBeInstanceOf(ConfiguratorSelect::class)
            ->and($select?->getSuffixActions())->toHaveKey('createConfigurator');
    }
});

it('explains widget rendering components and exposes component create actions', function (): void {
    $group = ComponentSection::make();

    expect($group)->toBeInstanceOf(Group::class);

    $components = collect(layoutBuilderFlattenRawComponentTree([$group]));
    $callout = $components->first(fn (mixed $component): bool => $component instanceof Callout);

    expect($callout)->toBeInstanceOf(Callout::class)
        ->and($callout?->getHeading())->toBe(__('capell-layout-builder::generic.widget_rendering_components'))
        ->and($callout?->getDescription())->toBe(__('capell-layout-builder::generic.widget_rendering_callout_description'));

    expect($components->contains(fn (mixed $component): bool => $component instanceof Section))->toBeFalse();

    $selects = $components
        ->filter(fn (mixed $component): bool => $component instanceof ComponentSelect)
        ->map(function (ComponentSelect $component): ComponentSelect {
            $mountedSelect = layoutBuilderMountedComponentTree([$component])[0] ?? null;
            expect($mountedSelect)->toBeInstanceOf(ComponentSelect::class);

            return $mountedSelect;
        })
        ->keyBy(fn (ComponentSelect $component): string => $component->getName());

    expect($selects)->toHaveKeys([
        'component',
        'component_item',
    ]);

    foreach (['component', 'component_item'] as $name) {
        $select = $selects[$name] ?? null;
        expect($select)->toBeInstanceOf(ComponentSelect::class);
        expect($select?->getSuffixActions())->toHaveKey('createComponent');
    }
});

it('replaces the widget display tab with focused presentation tabs', function (): void {
    $configurator = new DefaultWidgetConfigurator;
    $components = $configurator->make(Schema::make()->operation('editOption'));

    $presentationTabs = collect(layoutBuilderMountedComponentTree($components))
        ->first(fn (mixed $component): bool => $component instanceof Tabs);

    expect($presentationTabs)->toBeInstanceOf(Tabs::class);

    $tabComponents = $presentationTabs->getChildSchema()->getComponents();

    $tabs = collect(is_array($tabComponents) ? $tabComponents : [])
        ->filter(fn (mixed $component): bool => $component instanceof Tab)
        ->map(fn (Tab $component): ?string => layoutBuilderHtmlableText($component->getLabel()))
        ->values()
        ->all();

    expect($tabs)->toContain('Placement')
        ->toContain('Items')
        ->toContain('Appearance')
        ->toContain('Background')
        ->toContain('Rendering')
        ->not->toContain('Display')
        ->not->toContain('Layout')
        ->not->toContain('Spacing')
        ->not->toContain('Style');
});

/**
 * @return array<int, mixed>
 */
function layoutBuilderWidgetTypeAdminTabComponents(): array
{
    $adminTab = new ReflectionMethod(WidgetTypeConfigurator::class, 'adminTab');

    $mountedTab = layoutBuilderMountedComponentTree([$adminTab->invoke(new WidgetTypeConfigurator)])[0] ?? null;

    if (! is_object($mountedTab) || ! method_exists($mountedTab, 'getDefaultChildComponents')) {
        return [];
    }

    $components = $mountedTab->getDefaultChildComponents();

    return is_array($components) ? layoutBuilderMountedComponentTree($components) : [];
}

function layoutBuilderHtmlableText(Htmlable|string|null $value): ?string
{
    if ($value instanceof Htmlable) {
        return $value->toHtml();
    }

    return $value;
}

/**
 * @param  array<int, mixed>  $components
 * @return array<int, mixed>
 */
function layoutBuilderMountedComponentTree(array $components): array
{
    return Schema::make(new LayoutBuilderCoverageSchemaHarness)
        ->components($components)
        ->getComponents();
}

/**
 * @param  array<int, mixed>  $components
 * @return array<int, mixed>
 */
function layoutBuilderFlattenComponentTree(array $components): array
{
    $flattened = [];

    foreach ($components as $component) {
        $flattened[] = $component;
        if (! is_object($component)) {
            continue;
        }

        if (! method_exists($component, 'getDefaultChildComponents')) {
            continue;
        }

        $children = [];

        if (method_exists($component, 'getChildSchemas')) {
            foreach ($component->getChildSchemas() as $childSchema) {
                if ($childSchema instanceof Schema) {
                    array_push($children, ...$childSchema->getComponents());
                }
            }
        }

        if ($children === []) {
            $children = $component->getDefaultChildComponents();
        }

        if ($children instanceof Schema) {
            $children = $children->getComponents();
        }

        if (! is_array($children)) {
            continue;
        }

        array_push($flattened, ...layoutBuilderFlattenComponentTree($children));
    }

    return $flattened;
}

/**
 * @param  array<int, mixed>  $components
 * @return array<int, mixed>
 */
function layoutBuilderFlattenRawComponentTree(array $components): array
{
    $flattened = [];

    foreach ($components as $component) {
        $flattened[] = $component;

        if (! is_object($component)) {
            continue;
        }

        foreach (layoutBuilderRawChildComponentGroups($component) as $childComponents) {
            array_push($flattened, ...layoutBuilderFlattenRawComponentTree($childComponents));
        }
    }

    return $flattened;
}

/**
 * @return array<int, array<int, mixed>>
 */
function layoutBuilderRawChildComponentGroups(object $component): array
{
    $class = new ReflectionClass($component);

    while ($class !== false) {
        if (! $class->hasProperty('childComponents')) {
            $class = $class->getParentClass();

            continue;
        }

        $property = $class->getProperty('childComponents');
        $groups = $property->getValue($component);

        if (! is_array($groups)) {
            return [];
        }

        return collect($groups)
            ->filter(fn (mixed $children): bool => is_array($children))
            ->values()
            ->all();
    }

    return [];
}
