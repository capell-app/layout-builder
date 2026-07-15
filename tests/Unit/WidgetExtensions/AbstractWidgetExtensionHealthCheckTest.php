<?php

declare(strict_types=1);

use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\Frontend\Data\Assets\FrontendResourceData;
use Capell\Frontend\Data\Assets\FrontendResourceGroupData;
use Capell\Frontend\Data\Assets\PublicResourceSourceData;
use Capell\Frontend\Support\Assets\FrontendResourceRegistry;
use Capell\LayoutBuilder\Health\AbstractWidgetExtensionHealthCheck;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleFilamentWidget;
use Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions\ExampleWidgetExtensionDefinition;
use Illuminate\Contracts\View\Factory as ViewFactory;

final class ExampleWidgetHealthCheck extends AbstractWidgetExtensionHealthCheck
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }

    protected static function definitionKey(): string
    {
        return 'capell-app.slideshow';
    }

    protected static function filamentWidget(): string
    {
        return ExampleFilamentWidget::class;
    }

    protected static function fallbackView(): string
    {
        return 'widget-health-test::widget';
    }

    protected static function resourceGroups(): array
    {
        return ['capell-app.widget-slideshow'];
    }
}

it('checks every required widget extension package surface', function (): void {
    resolve(WidgetExtensionRegistry::class)->register(ExampleWidgetExtensionDefinition::make());
    resolve(ViewFactory::class)->addNamespace('widget-health-test', __DIR__ . '/../../Fixtures/views/widget-extension');
    resolve(FrontendResourceRegistry::class)->register(new FrontendResourceGroupData(
        key: 'capell-app.widget-slideshow',
        label: 'Slideshow',
        package: 'capell-app/layout-builder',
        resources: [FrontendResourceData::style(
            handle: 'capell-app/layout-builder:slideshow',
            package: 'capell-app/layout-builder',
            source: new PublicResourceSourceData('vendor/capell/slideshow.css'),
            loadingStrategy: PresentationLoadingStrategy::Visible,
        )],
    ));

    $diagnostics = ExampleWidgetHealthCheck::runDiagnostics();

    expect($diagnostics)->toHaveCount(4)
        ->and($diagnostics->every(static fn (DoctorCheckResultData $result): bool => $result->passed))->toBeTrue()
        ->and(ExampleWidgetHealthCheck::passed())->toBeTrue();
});
