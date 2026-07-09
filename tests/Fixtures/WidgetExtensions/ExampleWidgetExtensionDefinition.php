<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionCapabilitiesData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;

final class ExampleWidgetExtensionDefinition
{
    public static function make(
        string $packageName = 'capell-app/widget-slideshow',
        string $fallbackView = 'capell-widget-slideshow::widget',
    ): WidgetExtensionDefinitionData {
        return new WidgetExtensionDefinitionData(
            key: 'capell-app.slideshow',
            packageName: $packageName,
            stateVersion: 2,
            filamentWidget: ExampleFilamentWidget::class,
            inputData: ExampleInputData::class,
            renderData: ExampleRenderData::class,
            fallbackView: $fallbackView,
            components: ['blade' => 'capell::widgets.capell-app.slideshow'],
            resourceGroups: ['capell-app.widget-slideshow'],
            defaultPresentationSettings: ['variant' => 'default'],
            defaultInteractions: [['type' => 'carousel']],
            capabilities: new WidgetExtensionCapabilitiesData(
                supportsInteractions: true,
                requiresInstanceIdentity: true,
            ),
            stateUpcaster: ExampleStateUpcaster::class,
            batchPayloadResolver: ExampleBatchPayloadResolver::class,
            dependencyResolver: ExampleDependencyResolver::class,
        );
    }
}
