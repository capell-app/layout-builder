@props([
    'widgets' => [],
    'context' => [],
])

@php
    use Capell\Core\Actions\Interactions\ResolveInteractionTriggersAction;
    use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
    use Capell\Frontend\Exceptions\WidgetLibraryException;
    use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
    use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
    use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;

    if (! $widgets) {
        return '';
    }

    throw_unless(is_array($widgets), WidgetLibraryException::class, 'The "widgets" prop must be an array.', ['widgets' => $widgets]);
@endphp

@foreach (array_values($widgets) as $widgetIndex => $widgetData)
    {{-- format-ignore-start --}}
    @php
        /** @var LayoutWidgetRegistry $registry */
        $registry = resolve(LayoutWidgetRegistry::class);
        $widget = $registry->get($widgetData['type'], LayoutWidgetTarget::FrontendBlade);
        $definition = $registry->definition($widgetData['type'], LayoutWidgetTarget::FrontendBlade);

        if (! $widget) {
            throw new WidgetLibraryException(sprintf("Widget component for type '%s' not found.", $widgetData['type']), ['widgetData' => $widgetData]);
        }

        if (! isset($widgetData['data']) || ! is_array($widgetData['data'])) {
            throw new WidgetLibraryException(sprintf("Widget data for type '%s' is missing or invalid.", $widgetData['type']), ['widgetData' => $widgetData]);
        }

        $componentData = $widgetData['data'];
        unset($componentData['__capell']);

        $settings = ResolvePresentationSettingsAction::make()->fromWidgetBlockData(
            $widgetData,
            $definition?->defaultPresentationSettings ?? [],
        );
        $interactions = ResolveInteractionTriggersAction::make()->fromWidgetBlockData(
            $widgetData,
            $definition?->defaultInteractionTriggers ?? [],
        );
        $resourcePublicIds = collect($definition?->resourceGroups ?? [])
            ->map(fn (string $resourceGroup): string => LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId($resourceGroup))
            ->values()
            ->all();
    @endphp
    {{-- format-ignore-end --}}
    <x-capell-layout-builder::layout-widgets.runtime-wrapper
        :settings="$settings"
        :resource-public-ids="$resourcePublicIds"
    >
        <x-dynamic-component
            :component="$widget"
            :attributes="$attributes->merge($componentData, escape: false)"
            :$context
        />
        <x-capell::interactions
            class="mt-4"
            :triggers="$interactions"
        />
    </x-capell-layout-builder::layout-widgets.runtime-wrapper>
@endforeach
