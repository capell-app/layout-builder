# Widget extensions

Widget extensions register one canonical definition that drives Content Builder discovery and the legacy Blade widget registry. Register through `WidgetExtensionRegistrar`; it is safe whether Layout Builder's registry has already resolved or resolves later in the provider lifecycle.

```php
use Capell\Core\Enums\InteractionBehavior;
use Capell\Core\Enums\InteractionTargetType;
use Capell\Core\Enums\InteractionTriggerEvent;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionCapabilitiesData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Enums\WidgetPresentationCapability;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistrar;

$this->app->make(WidgetExtensionRegistrar::class)->register(
    new WidgetExtensionDefinitionData(
        key: 'acme.slideshow',
        packageName: 'acme/widget-slideshow',
        stateVersion: 1,
        filamentWidget: SlideshowFilamentWidget::class,
        inputData: SlideshowInputData::class,
        renderData: SlideshowRenderData::class,
        fallbackView: 'acme-slideshow::widget',
        components: [
            'blade' => 'capell::widgets.acme.slideshow',
        ],
        resourceGroups: ['acme.slideshow'],
        defaultResourceLoadingStrategy: PresentationLoadingStrategy::Visible,
        resourceGroupLoadingStrategies: [
            'acme.slideshow' => PresentationLoadingStrategy::Interaction,
        ],
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
        stateUpcaster: SlideshowStateUpcaster::class,
        batchPayloadResolver: SlideshowPayloadResolver::class,
        dependencyResolver: SlideshowDependencyResolver::class,
    ),
);
```

The Filament widget's `getWidgetName()` must return the same package-prefixed key. Input and render classes must extend `Spatie\LaravelData\Data`; optional resolver classes must implement their corresponding contracts under `Contracts\WidgetExtensions`.

Resource loading defaults use `PresentationLoadingStrategy`. The definition-wide default is `Eager`; per-group overrides may reference only keys declared in `resourceGroups`. Instance presentation settings remain authoritative over these defaults.

`presentationCapabilities` declares which standard presentation controls the widget supports. Interactive definitions must explicitly list their supported trigger events, behaviors, and target types using Capell's interaction enums. Non-interactive definitions retain empty lists by default.

At render time, `WidgetExtensionViewResolver` checks the stable `capell::widgets.{key}` theme slot on every call. The active theme and its parent may override that slot. If neither provides it, the package fallback is used; a missing fallback raises a diagnostic exception naming the widget and package.

Duplicate identical definitions are harmless. A different definition using an occupied key cannot replace the first registration and is exposed through `WidgetExtensionRegistry::collisions()` for diagnostics.
