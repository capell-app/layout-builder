<?php

declare(strict_types=1);

use Capell\Admin\Support\Widgets\WidgetDiscovery;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\LayoutBuilder\Actions\LayoutWidgets\BuildLayoutWidgetResourceUsagesAction;
use Capell\LayoutBuilder\Data\LayoutWidgets\LayoutWidgetDefinitionData;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionDefinitionAdapter;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistrar;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ConflictingFilamentWidget;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleFilamentWidget;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Illuminate\Container\Container;

it('binds the canonical registry through the Layout Builder provider', function (): void {
    expect(app()->bound(WidgetExtensionRegistry::class))->toBeTrue()
        ->and(app(WidgetExtensionRegistry::class))->toBeInstanceOf(WidgetExtensionRegistry::class)
        ->and(app()->bound(WidgetExtensionRegistrar::class))->toBeTrue();
});

it('adapts accepted extensions into legacy rendering and Filament discovery while preserving legacy widgets', function (): void {
    $legacyRegistry = app(LayoutWidgetRegistry::class);
    $legacyDefinition = LayoutWidgetDefinitionData::frontendBlade('legacy-banner', 'legacy::banner');
    $legacyRegistry->registerDefinition($legacyDefinition);

    $registry = new WidgetExtensionRegistry(new WidgetExtensionDefinitionAdapter(app()));
    $definition = ExampleWidgetExtensionDefinition::make();
    $registry->register($definition);
    $registry->register(ExampleWidgetExtensionDefinition::make());

    $adaptedDefinition = $legacyRegistry->definition('capell-app.slideshow', LayoutWidgetTarget::FrontendBlade);

    expect($legacyRegistry->definition('legacy-banner', LayoutWidgetTarget::FrontendBlade))->toBe($legacyDefinition)
        ->and($adaptedDefinition?->component)->toBe(WidgetExtensionDefinitionAdapter::GATED_COMPONENT)
        ->and($adaptedDefinition?->defaultLoadingStrategy)->toBe(PresentationLoadingStrategy::Visible)
        ->and($adaptedDefinition?->resourceGroupLoadingStrategies)->toBe([
            'capell-app.widget-slideshow.interaction' => PresentationLoadingStrategy::Interaction,
        ])
        ->and($adaptedDefinition?->defaultPresentationSettings['loading_strategy'] ?? null)->toBe('visible')
        ->and(app(WidgetDiscovery::class)->registeredWidgets()['capell-app.slideshow'] ?? null)->toBe(ExampleFilamentWidget::class);
});

it('registers an extension declared before canonical registry resolution', function (): void {
    $container = new Container;
    $registry = new WidgetExtensionRegistry;
    $container->singleton(WidgetExtensionRegistry::class, fn (): WidgetExtensionRegistry => $registry);
    $registrar = new WidgetExtensionRegistrar($container);

    $registrar->register(ExampleWidgetExtensionDefinition::make());

    expect($registry->all())->toBe([]);

    $container->make(WidgetExtensionRegistry::class);

    expect($registry->definition('capell-app.slideshow'))->not->toBeNull();
});

it('registers an extension declared after canonical registry resolution without duplication', function (): void {
    $container = new Container;
    $registry = new WidgetExtensionRegistry;
    $container->instance(WidgetExtensionRegistry::class, $registry);
    $container->make(WidgetExtensionRegistry::class);
    $registrar = new WidgetExtensionRegistrar($container);

    $registrar->register(ExampleWidgetExtensionDefinition::make());
    $registrar->register(ExampleWidgetExtensionDefinition::make());

    expect($registry->all())->toHaveCount(1)
        ->and($registry->collisions())->toBe([]);
});

it('carries global and per-group loading defaults through the legacy resource usage contract', function (): void {
    $registry = new WidgetExtensionRegistry(new WidgetExtensionDefinitionAdapter(app()));
    $registry->register(ExampleWidgetExtensionDefinition::make());

    $usages = BuildLayoutWidgetResourceUsagesAction::run([
        ['type' => 'capell-app.slideshow', 'data' => []],
    ], LayoutWidgetTarget::FrontendBlade);

    expect($usages)->toHaveCount(2)
        ->and($usages[0]->resourceGroup)->toBe('capell-app.widget-slideshow')
        ->and($usages[0]->presentation->loadingStrategy)->toBe(PresentationLoadingStrategy::Visible)
        ->and($usages[1]->resourceGroup)->toBe('capell-app.widget-slideshow.interaction')
        ->and($usages[1]->presentation->loadingStrategy)->toBe(PresentationLoadingStrategy::Interaction);

    $overriddenUsages = BuildLayoutWidgetResourceUsagesAction::run([
        [
            'type' => 'capell-app.slideshow',
            'data' => [
                '__capell' => [
                    'presentation' => ['loading_strategy' => PresentationLoadingStrategy::Idle->value],
                ],
            ],
        ],
    ], LayoutWidgetTarget::FrontendBlade);

    expect($overriddenUsages[0]->presentation->loadingStrategy)->toBe(PresentationLoadingStrategy::Idle)
        ->and($overriddenUsages[1]->presentation->loadingStrategy)->toBe(PresentationLoadingStrategy::Idle);
});

it('makes canonical adapters authoritative when ordinary registrations arrive first', function (): void {
    $container = new Container;
    $layoutWidgets = new LayoutWidgetRegistry;
    $filamentWidgets = new WidgetDiscovery;
    $container->instance(LayoutWidgetRegistry::class, $layoutWidgets);
    $container->instance(WidgetDiscovery::class, $filamentWidgets);

    $layoutWidgets->register('capell-app.slideshow', LayoutWidgetTarget::FrontendBlade, 'legacy::conflict');
    $filamentWidgets->register(ConflictingFilamentWidget::class);

    $registry = new WidgetExtensionRegistry(new WidgetExtensionDefinitionAdapter($container));
    $registry->register(ExampleWidgetExtensionDefinition::make());

    expect($layoutWidgets->get('capell-app.slideshow', LayoutWidgetTarget::FrontendBlade))
        ->toBe(WidgetExtensionDefinitionAdapter::GATED_COMPONENT)
        ->and($filamentWidgets->registeredWidgets()['capell-app.slideshow'] ?? null)
        ->toBe(ExampleFilamentWidget::class);
});

it('keeps canonical adapters authoritative when ordinary registrations arrive later', function (): void {
    $container = new Container;
    $layoutWidgets = new LayoutWidgetRegistry;
    $filamentWidgets = new WidgetDiscovery;
    $container->instance(LayoutWidgetRegistry::class, $layoutWidgets);
    $container->instance(WidgetDiscovery::class, $filamentWidgets);

    $registry = new WidgetExtensionRegistry(new WidgetExtensionDefinitionAdapter($container));
    $registry->register(ExampleWidgetExtensionDefinition::make());

    $layoutWidgets->register('capell-app.slideshow', LayoutWidgetTarget::FrontendBlade, 'legacy::conflict');
    $filamentWidgets->register(ConflictingFilamentWidget::class);

    expect($layoutWidgets->get('capell-app.slideshow', LayoutWidgetTarget::FrontendBlade))
        ->toBe(WidgetExtensionDefinitionAdapter::GATED_COMPONENT)
        ->and($filamentWidgets->registeredWidgets()['capell-app.slideshow'] ?? null)
        ->toBe(ExampleFilamentWidget::class);
});

it('preserves ordinary replacement behavior for unprefixed legacy layout widgets', function (): void {
    $registry = new LayoutWidgetRegistry;

    $registry->register('legacy-banner', LayoutWidgetTarget::FrontendBlade, 'legacy::first');
    $registry->register('legacy-banner', LayoutWidgetTarget::FrontendBlade, 'legacy::second');

    expect($registry->get('legacy-banner', LayoutWidgetTarget::FrontendBlade))->toBe('legacy::second');
});
