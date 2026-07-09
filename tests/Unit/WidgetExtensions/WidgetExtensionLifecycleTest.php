<?php

declare(strict_types=1);

use Capell\Admin\Support\Widgets\WidgetDiscovery;
use Capell\LayoutBuilder\Data\LayoutWidgets\LayoutWidgetDefinitionData;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionDefinitionAdapter;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistrar;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
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

    expect($legacyRegistry->definition('legacy-banner', LayoutWidgetTarget::FrontendBlade))->toBe($legacyDefinition)
        ->and($legacyRegistry->get('capell-app.slideshow', LayoutWidgetTarget::FrontendBlade))->toBe('capell-widget-slideshow::widget')
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
