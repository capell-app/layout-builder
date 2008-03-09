<?php

declare(strict_types=1);

use Capell\Core\Data\Media\MediaCompositionGuidanceData;
use Capell\Core\Models\Blueprint;
use Capell\Core\Support\Media\MediaCompositionGuidanceRegistry;
use Capell\LayoutBuilder\Filament\Components\Forms\BackgroundSchema;
use Capell\LayoutBuilder\Filament\Components\Forms\Widget\Tab\WidgetPresentationTabs;
use Capell\LayoutBuilder\Filament\Configurators\Types\WidgetTypeConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\HeroBannerWidgetConfigurator;
use Capell\LayoutBuilder\Filament\Configurators\Widgets\ModernHeroBannerConfigurator;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\Media\BackgroundCompositionGuidance;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderCoverageSchemaHarness;
use Capell\LayoutBuilder\Tests\Fixtures\LayoutBuilderStatefulSchemaHarness;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    config()->set('capell.media.crop_presets.hero-wide', ['label' => 'Hero wide', 'ratio' => '16:9', 'width' => 1920, 'height' => 1080]);
    Storage::fake('public');
    Storage::disk('public')->put('guides/hero-wide.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
    Storage::disk('public')->put('guides/hero-split.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

    $registry = resolve(MediaCompositionGuidanceRegistry::class);

    foreach (['hero-wide', 'hero-split'] as $key) {
        $registry->register(new MediaCompositionGuidanceData(
            key: $key,
            label: 'Guide ' . $key,
            preset: 'hero-wide',
            templatePath: 'guides/' . $key . '.svg',
            templateVersion: '1',
            instructions: 'Keep the left third quiet for ' . $key,
        ));
    }
});

/**
 * @param  array<array-key, mixed>  $components
 * @param  array<string, mixed>  $state
 * @return list<Field>
 */
function backgroundGuidanceFields(array $components, array $state = [], ?Widget $record = null): array
{
    $schema = Schema::make(new LayoutBuilderStatefulSchemaHarness)
        ->statePath('data')
        ->components(array_values(array_filter($components, fn (mixed $component): bool => $component instanceof Component)))
        ->record($record);
    $schema->fill($state);

    $fields = [];
    $walk = function (Schema $container) use (&$walk, &$fields): void {
        foreach ($container->getComponents(withHidden: true) as $component) {
            if ($component instanceof Field && in_array($component->getName(), ['background_image', 'data.backgroundImage'], true)) {
                $fields[] = $component;
            }

            if ($component instanceof Component) {
                foreach ($component->getChildSchemas(withHidden: true) as $child) {
                    $walk($child);
                }
            }
        }
    };
    $walk($schema);

    return $fields;
}

function backgroundGuidanceHtml(Field $field): string
{
    $schema = $field->getChildSchema(Field::ABOVE_CONTENT_SCHEMA_KEY);

    if (! $schema instanceof Schema) {
        return '';
    }

    return collect($schema->getComponents(withHidden: true))
        ->map(fn (mixed $component): string => match (true) {
            $component instanceof Htmlable => $component->toHtml(),
            is_object($component) && method_exists($component, 'getContent') && $component->getContent() instanceof Htmlable => $component->getContent()->toHtml(),
            default => '',
        })
        ->implode('');
}

/**
 * @param  array<string, mixed>  $guidance
 */
function backgroundGuidanceBlueprint(array $guidance): Blueprint
{
    return Blueprint::factory()->create(['admin' => [BackgroundCompositionGuidance::ADMIN_KEY => $guidance]]);
}

it('shows the blueprint guide on a new widget background upload using live blueprint state', function (): void {
    $blueprint = backgroundGuidanceBlueprint(['background_image' => 'hero-wide']);

    $fields = backgroundGuidanceFields(BackgroundSchema::make(), ['blueprint_id' => $blueprint->getKey()]);

    expect($fields)->toHaveCount(1)
        ->and(BackgroundCompositionGuidance::resolveKey($fields[0]))->toBe('hero-wide')
        ->and(backgroundGuidanceHtml($fields[0]))->toContain('Keep the left third quiet for hero-wide');
});

it('shows the guide when editing a widget whose blueprint relation is loaded', function (): void {
    $blueprint = backgroundGuidanceBlueprint(['background_image' => 'hero-split']);
    $widget = Widget::factory()->create(['blueprint_id' => $blueprint->getKey()]);
    $widget->setRelation('blueprint', $blueprint);

    $fields = backgroundGuidanceFields([WidgetPresentationTabs::backgroundTab()], ['background_mode' => 'image'], $widget);

    expect($fields)->toHaveCount(1)
        ->and(BackgroundCompositionGuidance::resolveKey($fields[0]))->toBe('hero-split');
});

it('covers hero banner uploads and modern hero banner URL fields with the same capability', function (): void {
    $blueprint = backgroundGuidanceBlueprint(['background_image' => 'hero-wide']);
    $detailsTab = (new ReflectionMethod(HeroBannerWidgetConfigurator::class, 'detailsTab'))->invoke(new HeroBannerWidgetConfigurator);
    assert($detailsTab instanceof Component);

    $upload = backgroundGuidanceFields([$detailsTab], ['blueprint_id' => $blueprint->getKey()]);
    $url = backgroundGuidanceFields(ModernHeroBannerConfigurator::getFormSchema(), ['blueprint_id' => $blueprint->getKey()]);

    expect($upload)->toHaveCount(1)
        ->and($upload[0])->toBeInstanceOf(SpatieMediaLibraryFileUpload::class)
        ->and(backgroundGuidanceHtml($upload[0]))->toContain('Keep the left third quiet for hero-wide')
        ->and($url)->toHaveCount(1)
        ->and($url[0])->toBeInstanceOf(TextInput::class)
        ->and(backgroundGuidanceHtml($url[0]))->toContain('Keep the left third quiet for hero-wide')
        ->toContain('guides/hero-wide.svg');
});

it('selects the guide matching the current blueprint and variant', function (): void {
    $variantBlueprint = backgroundGuidanceBlueprint(['background_image' => [
        'default' => 'hero-wide',
        'variant_state' => 'data.textAlign',
        'variants' => ['left' => 'hero-split'],
    ]]);
    $otherBlueprint = backgroundGuidanceBlueprint(['background_image' => 'hero-split']);
    $plainBlueprint = Blueprint::factory()->create(['admin' => []]);

    $resolve = fn (array $state): ?string => BackgroundCompositionGuidance::resolveKey(
        backgroundGuidanceFields(ModernHeroBannerConfigurator::getFormSchema(), $state)[0],
    );

    expect($resolve(['blueprint_id' => $variantBlueprint->getKey(), 'data' => ['textAlign' => 'center']]))->toBe('hero-wide')
        ->and($resolve(['blueprint_id' => $variantBlueprint->getKey(), 'data' => ['textAlign' => 'left']]))->toBe('hero-split')
        ->and($resolve(['blueprint_id' => $otherBlueprint->getKey()]))->toBe('hero-split')
        ->and($resolve(['blueprint_id' => $plainBlueprint->getKey()]))->toBeNull();
});

it('leaves background fields unchanged when no guidance is configured', function (): void {
    $blueprint = Blueprint::factory()->create(['admin' => ['image_source_policy' => ['image' => 'media']]]);

    $fields = backgroundGuidanceFields(BackgroundSchema::make(), ['blueprint_id' => $blueprint->getKey()]);

    expect(BackgroundCompositionGuidance::resolveKey($fields[0]))->toBeNull()
        ->and(backgroundGuidanceHtml($fields[0]))->toBe('')
        ->and(backgroundGuidanceHtml(backgroundGuidanceFields(BackgroundSchema::make())[0]))->toBe('');
});

it('offers registered guides in the blueprint admin tab', function (): void {
    expect(BackgroundCompositionGuidance::options())->toMatchArray([
        'hero-wide' => 'Guide hero-wide',
        'hero-split' => 'Guide hero-split',
    ]);

    $adminTab = (new ReflectionMethod(WidgetTypeConfigurator::class, 'adminTab'))->invoke(new WidgetTypeConfigurator);
    assert($adminTab instanceof Component);
    $names = collect(Schema::make(new LayoutBuilderCoverageSchemaHarness)->components([$adminTab])->getFlatFields(withHidden: true))
        ->keys()
        ->all();

    expect($names)->toContain('admin.composition_guidance.background_image');
});

it('keeps guidance out of form state and public widget data', function (): void {
    $blueprint = backgroundGuidanceBlueprint(['background_image' => 'hero-wide']);
    $widget = Widget::factory()->create(['blueprint_id' => $blueprint->getKey(), 'meta' => ['background_color' => '#000000']]);

    $schema = Schema::make(new LayoutBuilderStatefulSchemaHarness)
        ->statePath('data')
        ->components(array_values(array_filter(BackgroundSchema::make(), fn (mixed $component): bool => $component instanceof Component)))
        ->record($widget);
    $schema->fill(['blueprint_id' => $blueprint->getKey()]);

    $encodedState = json_encode($schema->getState(shouldCallHooksBefore: false));
    $encodedWidget = json_encode($widget->fresh()?->toArray());

    expect($encodedState)->not->toContain('hero-wide')
        ->not->toContain('composition_guidance')
        ->and($encodedWidget)->not->toContain('hero-wide')
        ->not->toContain('composition_guidance');
});
