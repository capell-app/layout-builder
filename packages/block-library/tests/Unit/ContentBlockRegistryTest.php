<?php

declare(strict_types=1);

use Capell\BlockLibrary\Actions\BuildContentBlockDemoDataAction;
use Capell\BlockLibrary\Actions\RegisterContentBlockDefinitionProviderAction;
use Capell\BlockLibrary\Actions\RegisterDefaultBlockLibraryAction;
use Capell\BlockLibrary\Actions\ResolveContentBlockComponentAction;
use Capell\BlockLibrary\Actions\ResolveRequestedContentBlockTypeAction;
use Capell\BlockLibrary\Contracts\ContentBlockDefinitionProvider;
use Capell\BlockLibrary\Data\ContentBlockDefinitionData;
use Capell\BlockLibrary\Enums\ContentBlockConfiguratorEnum;
use Capell\BlockLibrary\Enums\LayoutTypeEnum;
use Capell\BlockLibrary\Filament\Components\Forms\Content\DetailsSchema;
use Capell\BlockLibrary\Filament\Components\Forms\Content\TypeSelect;
use Capell\BlockLibrary\Filament\Configurators\BlockLibrary\AccordionContentBlockConfigurator;
use Capell\BlockLibrary\Support\ContentBlockRegistry;
use Capell\Core\Models\Type;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Blade;

it('registers the main content blocks', function (): void {
    $registry = new ContentBlockRegistry;

    RegisterDefaultBlockLibraryAction::run($registry);

    expect(array_keys($registry->all()))->toContain(
        'accordion',
        'call_to_action',
        'comparison',
        'counter',
        'divider',
        'faq',
        'features',
        'logos',
        'pricing',
        'stats',
        'table',
        'tabs',
        'team',
        'timeline',
    );
});

it('guards against duplicate block keys', function (): void {
    $registry = new ContentBlockRegistry;
    $definition = new ContentBlockDefinitionData(
        key: 'accordion',
        label: 'Accordion',
        description: 'Accordion panels.',
        icon: Heroicon::OutlinedQueueList,
        group: 'main',
        configurator: ContentBlockConfiguratorEnum::Accordion->value,
        component: 'capell-block-library::content-block.blocks.accordion',
    );

    $registry->register($definition);
    $registry->register($definition);
})->throws(InvalidArgumentException::class);

it('resolves block definitions from configurator classes and keys', function (): void {
    $registry = new ContentBlockRegistry;

    RegisterDefaultBlockLibraryAction::run($registry);

    expect($registry->getByConfigurator(AccordionContentBlockConfigurator::class)?->key)->toBe('accordion')
        ->and($registry->getByConfigurator(AccordionContentBlockConfigurator::getKey())?->key)->toBe('accordion');
});

it('registers content block definitions from another package provider', function (): void {
    $registry = new ContentBlockRegistry;
    $provider = new class implements ContentBlockDefinitionProvider
    {
        /**
         * @return iterable<ContentBlockDefinitionData>
         */
        public function definitions(): iterable
        {
            return [
                new ContentBlockDefinitionData(
                    key: 'package_accordion',
                    label: 'Package accordion',
                    description: 'A package-owned content block.',
                    icon: Heroicon::OutlinedQueueList,
                    group: 'package',
                    configurator: AccordionContentBlockConfigurator::class,
                    component: 'vendor-package::content-block.package-accordion',
                ),
            ];
        }
    };

    RegisterContentBlockDefinitionProviderAction::run($registry, $provider);

    expect($registry->get('package_accordion')?->component)->toBe('vendor-package::content-block.package-accordion')
        ->and($registry->getByConfigurator(AccordionContentBlockConfigurator::getKey())?->key)->toBe('package_accordion');
});

it('resolves the frontend component without string matching configurator names', function (): void {
    $registry = new ContentBlockRegistry;
    $registry->register(new ContentBlockDefinitionData(
        key: 'package_accordion',
        label: 'Package accordion',
        description: 'A package-owned content block.',
        icon: Heroicon::OutlinedQueueList,
        group: 'package',
        configurator: AccordionContentBlockConfigurator::class,
        component: 'vendor-package::content-block.package-accordion',
    ));

    app()->instance(ContentBlockRegistry::class, $registry);

    expect(ResolveContentBlockComponentAction::run(
        configurator: AccordionContentBlockConfigurator::getKey(),
        fallbackComponent: 'capell-block-library::content-block.fallback',
    ))->toBe('vendor-package::content-block.package-accordion');
});

it('resolves requested screenshot block types from query parameters', function (): void {
    $registry = new ContentBlockRegistry;

    RegisterDefaultBlockLibraryAction::run($registry);
    app()->instance(ContentBlockRegistry::class, $registry);
    request()->query->set('block', 'accordion');

    $type = ResolveRequestedContentBlockTypeAction::run();

    expect($type)->toBeInstanceOf(Type::class)
        ->and($type?->key)->toBe('accordion')
        ->and($type?->admin['configurator'])->toBe(AccordionContentBlockConfigurator::getKey());
});

it('creates the default content block type for generic create routes', function (): void {
    $registry = new ContentBlockRegistry;

    RegisterDefaultBlockLibraryAction::run($registry);
    app()->instance(ContentBlockRegistry::class, $registry);

    expect(Type::query()->where('type', LayoutTypeEnum::ContentBlock->value)->exists())->toBeFalse();

    $type = ResolveRequestedContentBlockTypeAction::make()->defaultType();

    expect($type->key)->toBe('content')
        ->and($type->default)->toBeTrue()
        ->and(Type::query()->where('type', LayoutTypeEnum::ContentBlock->value)->count())->toBe(1);
});

it('does not expose inline type creation from the content block details schema', function (): void {
    $configurator = Schema::make()->operation('create');

    $typeSelect = collect(DetailsSchema::make($configurator))
        ->first(fn (mixed $component): bool => $component instanceof TypeSelect);

    expect($typeSelect)->toBeInstanceOf(TypeSelect::class)
        ->and($typeSelect->hasCreateOptionActionFormSchema())->toBeFalse();
});

it('renders every registered content block demo component', function (): void {
    $registry = new ContentBlockRegistry;

    RegisterDefaultBlockLibraryAction::run($registry);
    app()->instance(ContentBlockRegistry::class, $registry);
    view()->addNamespace('capell-block-library', __DIR__ . '/../../resources/views');
    Blade::anonymousComponentPath(__DIR__ . '/../Fixtures/components', 'capell');

    foreach (array_keys($registry->all()) as $key) {
        $data = BuildContentBlockDemoDataAction::run($key);
        $html = Blade::render(
            '<x-dynamic-component :component="$definition->component" :asset="$asset" :meta="$meta" :summary="$summary" :title="$title" :link-text="$linkText" :url="$url" />',
            $data,
        );

        expect($html)->toContain('content-block');
    }
});
