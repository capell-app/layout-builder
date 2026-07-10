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

Canonical registrations are authoritative in both the legacy Blade registry and Filament discovery, so ordinary same-key registration order cannot replace them. Before Blade enters the public query guard, Layout Builder recursively discovers registered blocks, upcasts their state, removes reserved authoring metadata, calls `InputData::validateAndCreate()`, and batches instances by definition. A batch resolver receives `WidgetExtensionPayloadBatchData`, whose items contain only typed input Data, and must return the declared render Data object keyed by instance identity. It is called once per definition per public payload build. Definitions without a resolver are converted from validated input Data to render Data.

Package and theme views receive exactly two named values: the declared render Data object as `$widget` and a deliberately small `WidgetExtensionRenderContextData` as `$context`. They never receive saved arrays, instance identities, package metadata, editor paths, selectors, or signed URLs. Views remain responsible for context-appropriate escaping and field-specific rich-text sanitization; the platform does not blanket-sanitize final widget HTML because that would remove legitimate controls, media, and SVG. Payload validation or resolution failures produce a generic inert fallback without making the page unavailable.

`WidgetExtensionViewResolver` resolves the stable `capell::widgets.{key}` slot for every render, so active-theme and parent-theme switching remains request-safe. Package fallback views are not registered as legacy runtime components. All resolver work, including media hydration, must finish during the payload build: package and theme Blade views must execute zero queries.

Duplicate identical definitions are harmless. A different definition using an occupied key cannot replace the first registration and is exposed through `WidgetExtensionRegistry::collisions()` for diagnostics.

## Saved state and instance identity

Content Builder reserves `data.__capell` for platform metadata. Every registered widget receives a canonical, unique UUID in `data.__capell.instance_id` during hydration and dehydration. Existing valid unique identities remain stable; copied, cloned, imported, translated, templated, and nested widget state with a missing, invalid, or duplicate identity receives a new one. Presentation, interaction, and resource settings already stored beneath `__capell` are merged rather than replaced.

Cloning regenerates the root widget identity and every nested registered target before the cloned item enters Livewire state. State traversal is bounded to 64 levels and 10,000 array nodes. Over-limit normalization and authoring preparation leave the original state untouched; an over-limit unavailable-widget restoration aborts dehydration rather than persisting Capell's internal placeholder shape.

Canonical widget extensions also reserve `data.__capell.state_version`. Missing version metadata means version `1`. When saved state is older than the definition's `stateVersion`, Layout Builder resolves the declared `WidgetExtensionStateUpcaster` and calls it with the widget data and the source and target versions. Upcasters must be deterministic and side-effect free, must return an array, and must not query application state. The platform restores the reserved instance identity and writes the current state version after a successful upcast. Future, invalid, or non-upcastable versions are retained unchanged so an older application cannot corrupt newer package state.

Unknown widget blocks are opaque. Content Builder shows a generic unavailable-widget placeholder, does not render editable fields for the unknown payload, and restores the original type, data, and extension-owned keys on dehydration. State processors must therefore act only on keys present in Admin's `WidgetDiscovery`; they must never recursively inspect an unavailable widget's payload.

Layout Builder connects state versioning through Admin's tagged `ContentWidgetStateProcessor` seam. Admin owns identity normalization and remains fully functional when Layout Builder is absent; packages must not call Layout Builder directly from Content Builder.

## Content dependencies

An optional `WidgetExtensionDependencyResolver` receives the same validated input Data used by the public payload builder. It runs during content-graph rebuilds, never from Blade, and returns stable identifiers using this grammar:

- `media:<positive-integer>` for a media record.
- `content:<type>:<positive-integer>` where `type` is `page`, `layout`, or `widget`.

Malformed values, unknown types, zero/negative identifiers, resolver exceptions, and invalid widget input are ignored and recorded as non-sensitive diagnostics. Valid dependencies are deduplicated before strong content-graph edges are emitted for Page or Layout sources.
