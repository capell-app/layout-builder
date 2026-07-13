<?php

declare(strict_types=1);

use Capell\Admin\Filament\Widgets\ContentFilamentWidget;
use Capell\Core\Enums\InteractionBehavior;
use Capell\Core\Enums\InteractionTargetType;
use Capell\Core\Enums\InteractionTriggerEvent;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionCapabilitiesData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Enums\WidgetPresentationCapability;
use Capell\LayoutBuilder\Exceptions\InvalidWidgetExtensionDefinitionException;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleFilamentWidget;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleInputData;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleRenderData;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;

function slideshowWidgetExtensionDefinition(
    string $packageName = 'capell-app/widget-slideshow',
    string $fallbackView = 'capell-widget-slideshow::widget',
): WidgetExtensionDefinitionData {
    return ExampleWidgetExtensionDefinition::make($packageName, $fallbackView);
}

it('accepts a complete Blade widget extension definition', function (): void {
    $definition = slideshowWidgetExtensionDefinition();

    expect($definition->key)->toBe('capell-app.slideshow')
        ->and($definition->stateVersion)->toBe(2)
        ->and($definition->components)->toBe(['blade' => 'capell::widgets.capell-app.slideshow'])
        ->and($definition->defaultResourceLoadingStrategy)->toBe(PresentationLoadingStrategy::Visible)
        ->and($definition->resourceGroupLoadingStrategies)->toBe([
            'capell-app.widget-slideshow.interaction' => PresentationLoadingStrategy::Interaction,
        ])
        ->and($definition->capabilities->supportsInteractions)->toBeTrue()
        ->and($definition->capabilities->requiresInstanceIdentity)->toBeTrue()
        ->and($definition->capabilities->presentationCapabilities)->toContain(WidgetPresentationCapability::Width)
        ->and($definition->capabilities->supportedInteractionEvents)->toBe([InteractionTriggerEvent::Click])
        ->and($definition->capabilities->supportedInteractionBehaviors)->toBe([InteractionBehavior::InlineReveal])
        ->and($definition->capabilities->supportedInteractionTargetTypes)->toBe([InteractionTargetType::Widget]);
});

it('keeps resource and interaction capability defaults backward friendly', function (): void {
    $definition = new WidgetExtensionDefinitionData(
        key: 'capell-app.slideshow',
        packageName: 'capell-app/widget-slideshow',
        stateVersion: 1,
        filamentWidget: ExampleFilamentWidget::class,
        inputData: ExampleInputData::class,
        renderData: ExampleRenderData::class,
        fallbackView: 'capell-widget-slideshow::widget',
        components: ['blade' => 'capell::widgets.capell-app.slideshow'],
    );

    expect($definition->defaultResourceLoadingStrategy)->toBe(PresentationLoadingStrategy::Eager)
        ->and($definition->resourceGroupLoadingStrategies)->toBe([])
        ->and($definition->capabilities->presentationCapabilities)->toBe([])
        ->and($definition->capabilities->supportedInteractionEvents)->toBe([])
        ->and($definition->capabilities->supportedInteractionBehaviors)->toBe([])
        ->and($definition->capabilities->supportedInteractionTargetTypes)->toBe([]);
});

it('rejects resource loading defaults for undeclared groups', function (): void {
    expect(fn (): WidgetExtensionDefinitionData => new WidgetExtensionDefinitionData(
        key: 'capell-app.slideshow',
        packageName: 'capell-app/widget-slideshow',
        stateVersion: 1,
        filamentWidget: ExampleFilamentWidget::class,
        inputData: ExampleInputData::class,
        renderData: ExampleRenderData::class,
        fallbackView: 'capell-widget-slideshow::widget',
        components: ['blade' => 'capell::widgets.capell-app.slideshow'],
        resourceGroups: ['capell-app.widget-slideshow'],
        resourceGroupLoadingStrategies: [
            'capell-app.undeclared' => PresentationLoadingStrategy::Idle,
        ],
    ))->toThrow(InvalidWidgetExtensionDefinitionException::class, 'declared resource group');
});

it('validates explicit presentation and interaction capabilities', function (): void {
    expect(fn (): object => (new ReflectionClass(WidgetExtensionCapabilitiesData::class))->newInstanceArgs([
        false,
        false,
        ['width'],
    ]))->toThrow(InvalidWidgetExtensionDefinitionException::class, 'presentation capability')
        ->and(fn (): WidgetExtensionCapabilitiesData => new WidgetExtensionCapabilitiesData(
            supportsInteractions: true,
            supportedInteractionEvents: [InteractionTriggerEvent::Click],
            supportedInteractionBehaviors: [],
            supportedInteractionTargetTypes: [InteractionTargetType::Widget],
        ))->toThrow(InvalidWidgetExtensionDefinitionException::class, 'behavior')
        ->and(fn (): WidgetExtensionCapabilitiesData => new WidgetExtensionCapabilitiesData(
            supportsInteractions: false,
            supportedInteractionEvents: [InteractionTriggerEvent::Click],
            supportedInteractionBehaviors: [InteractionBehavior::Modal],
            supportedInteractionTargetTypes: [InteractionTargetType::Widget],
        ))->toThrow(InvalidWidgetExtensionDefinitionException::class, 'disabled');
});

it('rejects invalid widget extension definition contracts', function (Closure $makeDefinition, string $message): void {
    expect($makeDefinition(...))->toThrow(InvalidWidgetExtensionDefinitionException::class, $message);
})->with([
    'unprefixed key' => [
        fn (): WidgetExtensionDefinitionData => new WidgetExtensionDefinitionData(
            key: 'slideshow',
            packageName: 'capell-app/widget-slideshow',
            stateVersion: 1,
            filamentWidget: ExampleFilamentWidget::class,
            inputData: ExampleInputData::class,
            renderData: ExampleRenderData::class,
            fallbackView: 'capell-widget-slideshow::widget',
            components: ['blade' => 'capell::widgets.capell-app.slideshow'],
        ),
        'package-prefixed key',
    ],
    'non-positive version' => [
        fn (): WidgetExtensionDefinitionData => new WidgetExtensionDefinitionData(
            key: 'capell-app.slideshow',
            packageName: 'capell-app/widget-slideshow',
            stateVersion: 0,
            filamentWidget: ExampleFilamentWidget::class,
            inputData: ExampleInputData::class,
            renderData: ExampleRenderData::class,
            fallbackView: 'capell-widget-slideshow::widget',
            components: ['blade' => 'capell::widgets.capell-app.slideshow'],
        ),
        'positive state version',
    ],
    'missing Blade runtime' => [
        fn (): WidgetExtensionDefinitionData => new WidgetExtensionDefinitionData(
            key: 'capell-app.slideshow',
            packageName: 'capell-app/widget-slideshow',
            stateVersion: 1,
            filamentWidget: ExampleFilamentWidget::class,
            inputData: ExampleInputData::class,
            renderData: ExampleRenderData::class,
            fallbackView: 'capell-widget-slideshow::widget',
            components: ['inertia' => 'Widgets/Slideshow'],
        ),
        'Blade runtime',
    ],
    'invalid input data class' => [
        fn (): object => (new ReflectionClass(WidgetExtensionDefinitionData::class))->newInstanceArgs([
            'capell-app.slideshow',
            'capell-app/widget-slideshow',
            1,
            ExampleFilamentWidget::class,
            stdClass::class,
            ExampleRenderData::class,
            'capell-widget-slideshow::widget',
            ['blade' => 'capell::widgets.capell-app.slideshow'],
        ]),
        'input Data class',
    ],
    'mismatched Filament widget key' => [
        fn (): WidgetExtensionDefinitionData => new WidgetExtensionDefinitionData(
            key: 'capell-app.slideshow',
            packageName: 'capell-app/widget-slideshow',
            stateVersion: 1,
            filamentWidget: ContentFilamentWidget::class,
            inputData: ExampleInputData::class,
            renderData: ExampleRenderData::class,
            fallbackView: 'capell-widget-slideshow::widget',
            components: ['blade' => 'capell::widgets.capell-app.slideshow'],
        ),
        'canonical widget key',
    ],
]);

it('registers definitions idempotently and records conflicts without overwriting', function (): void {
    $registry = new WidgetExtensionRegistry;
    $accepted = slideshowWidgetExtensionDefinition();
    $duplicate = slideshowWidgetExtensionDefinition();
    $conflict = slideshowWidgetExtensionDefinition(packageName: 'capell-app/widget-gallery');

    $registry->register($accepted);
    $registry->register($duplicate);
    $registry->register($conflict);
    $registry->register($conflict);

    expect($registry->definition('capell-app.slideshow'))->toBe($accepted)
        ->and($registry->all())->toBe(['capell-app.slideshow' => $accepted])
        ->and($registry->collisions())->toHaveCount(1)
        ->and($registry->collisions()[0]->key)->toBe('capell-app.slideshow')
        ->and($registry->collisions()[0]->acceptedPackageName)->toBe('capell-app/widget-slideshow')
        ->and($registry->collisions()[0]->conflictingPackageName)->toBe('capell-app/widget-gallery');
});
