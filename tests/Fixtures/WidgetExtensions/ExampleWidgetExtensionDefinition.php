<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures\WidgetExtensions;

use Capell\Core\Enums\InteractionBehavior;
use Capell\Core\Enums\InteractionTargetType;
use Capell\Core\Enums\InteractionTriggerEvent;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionBatchPayloadResolver;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionDependencyResolver;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionStateUpcaster;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionCapabilitiesData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Enums\WidgetPresentationCapability;
use Spatie\LaravelData\Data;

final class ExampleWidgetExtensionDefinition
{
    /**
     * @param  class-string<WidgetExtensionBatchPayloadResolver>|null  $batchPayloadResolver
     * @param  class-string<WidgetExtensionDependencyResolver>|null  $dependencyResolver
     * @param  class-string<Data>  $inputData
     * @param  class-string<Data>  $renderData
     * @param  class-string<WidgetExtensionStateUpcaster>|null  $stateUpcaster
     */
    public static function make(
        string $packageName = 'capell-app/widget-slideshow',
        string $fallbackView = 'capell-widget-slideshow::widget',
        ?string $batchPayloadResolver = ExampleBatchPayloadResolver::class,
        ?string $dependencyResolver = ExampleDependencyResolver::class,
        int $stateVersion = 2,
        string $inputData = ExampleInputData::class,
        string $renderData = ExampleRenderData::class,
        ?string $stateUpcaster = ExampleStateUpcaster::class,
    ): WidgetExtensionDefinitionData {
        return new WidgetExtensionDefinitionData(
            key: 'capell-app.slideshow',
            packageName: $packageName,
            stateVersion: $stateVersion,
            filamentWidget: ExampleFilamentWidget::class,
            inputData: $inputData,
            renderData: $renderData,
            fallbackView: $fallbackView,
            components: ['blade' => 'capell::widgets.capell-app.slideshow'],
            resourceGroups: [
                'capell-app.widget-slideshow',
                'capell-app.widget-slideshow.interaction',
            ],
            defaultResourceLoadingStrategy: PresentationLoadingStrategy::Visible,
            resourceGroupLoadingStrategies: [
                'capell-app.widget-slideshow.interaction' => PresentationLoadingStrategy::Interaction,
            ],
            defaultPresentationSettings: ['variant' => 'default'],
            defaultInteractions: [[
                'event' => InteractionTriggerEvent::Click->value,
                'behavior' => InteractionBehavior::InlineReveal->value,
            ]],
            capabilities: new WidgetExtensionCapabilitiesData(
                supportsInteractions: true,
                requiresInstanceIdentity: true,
                presentationCapabilities: [
                    WidgetPresentationCapability::Width,
                    WidgetPresentationCapability::Alignment,
                    WidgetPresentationCapability::LoadingStrategy,
                ],
                supportedInteractionEvents: [InteractionTriggerEvent::Click],
                supportedInteractionBehaviors: [InteractionBehavior::InlineReveal],
                supportedInteractionTargetTypes: [InteractionTargetType::Widget],
            ),
            stateUpcaster: $stateUpcaster,
            batchPayloadResolver: $batchPayloadResolver,
            dependencyResolver: $dependencyResolver,
        );
    }
}
