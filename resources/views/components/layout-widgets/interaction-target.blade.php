@props([
    'widgetData' => [],
    'context' => [],
])

@php
    use Capell\Core\Actions\Interactions\ResolveInteractionTriggersAction;
    use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
    use Capell\Frontend\Exceptions\WidgetLibraryException;
    use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
    use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;

    throw_unless(is_array($widgetData), WidgetLibraryException::class, 'The lazy widget payload must be an array.', ['widgetData' => $widgetData]);

    /** @var LayoutWidgetRegistry $registry */
    $registry = resolve(LayoutWidgetRegistry::class);
    $widgetType = $widgetData['type'] ?? null;
    $widget = is_string($widgetType) ? $registry->get($widgetType, LayoutWidgetTarget::FrontendBlade) : null;
    $definition = is_string($widgetType) ? $registry->definition($widgetType, LayoutWidgetTarget::FrontendBlade) : null;

    if (! $widget || ! is_string($widgetType)) {
        throw new WidgetLibraryException('Lazy widget component was not found.', ['widgetData' => $widgetData]);
    }

    if (! isset($widgetData['data']) || ! is_array($widgetData['data'])) {
        throw new WidgetLibraryException('Lazy widget data is missing or invalid.', ['widgetData' => $widgetData]);
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
@endphp

<x-capell-layout-builder::layout-widgets.runtime-wrapper :settings="$settings">
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
