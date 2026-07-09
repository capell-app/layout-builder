<?php

declare(strict_types=1);

use Capell\Admin\Filament\Widgets\ContentFilamentWidget;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
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
        ->and($definition->capabilities->supportsInteractions)->toBeTrue()
        ->and($definition->capabilities->requiresInstanceIdentity)->toBeTrue();
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
        fn (): WidgetExtensionDefinitionData => new WidgetExtensionDefinitionData(
            key: 'capell-app.slideshow',
            packageName: 'capell-app/widget-slideshow',
            stateVersion: 1,
            filamentWidget: ExampleFilamentWidget::class,
            inputData: stdClass::class,
            renderData: ExampleRenderData::class,
            fallbackView: 'capell-widget-slideshow::widget',
            components: ['blade' => 'capell::widgets.capell-app.slideshow'],
        ),
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
