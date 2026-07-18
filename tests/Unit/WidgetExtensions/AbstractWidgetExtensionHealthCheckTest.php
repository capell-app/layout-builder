<?php

declare(strict_types=1);

use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\Frontend\Data\Assets\FrontendResourceData;
use Capell\Frontend\Data\Assets\FrontendResourceGroupData;
use Capell\Frontend\Data\Assets\PublicResourceSourceData;
use Capell\Frontend\Support\Assets\FrontendResourceRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetHealthCheck;
use Illuminate\Contracts\View\Factory as ViewFactory;

it('checks every required widget extension package surface', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    resolve(ViewFactory::class)->addNamespace('widget-health-test', __DIR__ . '/../../Fixtures/views/widget-extension');
    resolve(FrontendResourceRegistry::class)->register(new FrontendResourceGroupData(
        key: 'capell-app.widget-slideshow',
        label: 'Slideshow',
        package: 'capell-app/widget-slideshow',
        resources: [FrontendResourceData::style(
            handle: 'capell-app/widget-slideshow:style',
            package: 'capell-app/widget-slideshow',
            source: new PublicResourceSourceData('/vendor/capell/slideshow.css'),
            loadingStrategy: PresentationLoadingStrategy::Visible,
        )],
    ));

    $diagnostics = ExampleWidgetHealthCheck::runDiagnostics();

    expect($diagnostics)->toHaveCount(4)
        ->and($diagnostics->every(static fn (DoctorCheckResultData $result): bool => $result->passed))->toBeTrue()
        ->and(ExampleWidgetHealthCheck::passed())->toBeTrue();
});
