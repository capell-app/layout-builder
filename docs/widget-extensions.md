# Widget extensions

Widget extensions register one canonical definition that drives Content Builder discovery and the legacy Blade widget registry. Register through `WidgetExtensionRegistrar`; it is safe whether Layout Builder's registry has already resolved or resolves later in the provider lifecycle.

```php
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionCapabilitiesData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
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
        capabilities: new WidgetExtensionCapabilitiesData(
            supportsInteractions: true,
            requiresInstanceIdentity: true,
        ),
        stateUpcaster: SlideshowStateUpcaster::class,
        batchPayloadResolver: SlideshowPayloadResolver::class,
        dependencyResolver: SlideshowDependencyResolver::class,
    ),
);
```

The Filament widget's `getWidgetName()` must return the same package-prefixed key. Input and render classes must extend `Spatie\LaravelData\Data`; optional resolver classes must implement their corresponding contracts under `Contracts\WidgetExtensions`.

At render time, `WidgetExtensionViewResolver` checks the stable `capell::widgets.{key}` theme slot on every call. The active theme and its parent may override that slot. If neither provides it, the package fallback is used; a missing fallback raises a diagnostic exception naming the widget and package.

Duplicate identical definitions are harmless. A different definition using an occupied key cannot replace the first registration and is exposed through `WidgetExtensionRegistry::collisions()` for diagnostics.
