@php
    use Capell\Core\Actions\Interactions\ResolveInteractionTriggersAction;
    use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
    use Capell\Core\Enums\PresentationDeliveryMode;
    use Capell\Frontend\Facades\Frontend;
    use Capell\LayoutBuilder\Support\LayoutBlockWidgetResourceUsageContributor;
    use Capell\LayoutBuilder\Support\Livewire\OpaqueBlockReference;

    $blockComponent = $component;
    $occurrence = $blockData['occurrence'] ?? 1;
    $layoutKey = is_object($layout) && method_exists($layout, 'getKey') ? $layout->getKey() : 'global';
    $blockDomId = 'layout-block-' . hash('xxh128', (string) $layoutKey . ':' . (string) $containerKey . ':' . (string) $blockIndex);
    $blockMeta = is_array($blockData['meta'] ?? null) ? $blockData['meta'] : [];
    $presentation = ResolvePresentationSettingsAction::run(
        instanceSettings: is_array($blockMeta['presentation'] ?? null) ? $blockMeta['presentation'] : [],
        typeDefaults: is_array($block->type?->meta['presentation'] ?? null) ? $block->type->meta['presentation'] : [],
    );
    $isLazyFragment = $presentation->deliveryMode === PresentationDeliveryMode::LazyFragment;
    $blockReferenceData = [
        'container_key' => $containerKey,
        'block_key' => $blockData['widget_key'] ?? $blockData['block_key'] ?? $block->key,
        'widget_key' => $blockData['widget_key'] ?? $blockData['block_key'] ?? $block->key,
        'layout_id' => $layoutKey === 'global' ? null : $layoutKey,
        'language_id' => Frontend::language()?->getKey(),
        'occurrence' => $occurrence,
        'page_id' => Frontend::page()?->getKey(),
        'page_type' => Frontend::page()?->getMorphClass(),
        'site_id' => Frontend::site()?->getKey(),
        'block_data' => $blockData,
        'block_index' => $blockIndex,
    ];
    $blockReference = OpaqueBlockReference::encode($blockReferenceData);
    $withCurrentBlockFragment = function (array $trigger) use ($blockReference): array {
        if (($trigger['target_type'] ?? $trigger['target']['target_type'] ?? null) !== 'fragment') {
            return $trigger;
        }

        if (filled($trigger['fragment_reference'] ?? $trigger['target']['fragment_reference'] ?? null)) {
            return $trigger;
        }

        $trigger['fragment_reference'] = $blockReference;

        return $trigger;
    };
    $instanceInteractions = collect(is_array($blockMeta['interactions'] ?? null) ? $blockMeta['interactions'] : [])
        ->map(fn (mixed $trigger): mixed => is_array($trigger) ? $withCurrentBlockFragment($trigger) : $trigger)
        ->all();
    $typeDefaultInteractions = collect(is_array($block->type?->meta['interactions'] ?? null) ? $block->type->meta['interactions'] : [])
        ->map(fn (mixed $trigger): mixed => is_array($trigger) ? $withCurrentBlockFragment($trigger) : $trigger)
        ->all();
    $interactions = ResolveInteractionTriggersAction::run(
        instanceTriggers: $instanceInteractions,
        typeDefaultTriggers: $typeDefaultInteractions,
    );
    $resourceGroups = collect([
        ...(is_array($block->type?->meta['resource_groups'] ?? null) ? $block->type->meta['resource_groups'] : []),
        ...(is_array($blockMeta['resource_groups'] ?? null) ? $blockMeta['resource_groups'] : []),
    ])
        ->filter(fn (mixed $resourceGroup): bool => is_string($resourceGroup) && $resourceGroup !== '')
        ->unique()
        ->values()
        ->all();
    $resourcePublicIds = collect($resourceGroups)
        ->map(fn (string $resourceGroup): string => LayoutBlockWidgetResourceUsageContributor::publicId(
            (string) ($blockData['block_key'] ?? $block->key),
            $resourceGroup,
            (string) $containerKey,
            (int) $occurrence,
        ))
        ->all();
@endphp

@if ($isLazyFragment)
    <div
        id="{{ $blockDomId }}"
        data-deferred-fragment
        data-deferred-fragment-url="{{ url('/_fragments/' . rawurlencode($blockReference)) }}"
        class="deferred-fragment"
    ></div>
@elseif ($type === 'blade')
    <x-capell::widgets.runtime-wrapper
        :settings="$presentation"
        :resource-public-ids="$resourcePublicIds"
    >
        <div id="{{ $blockDomId }}" class="layout-block">
            <x-dynamic-component
                :component="$blockComponent"
                :$container
                :$containerColspan
                :$containerKey
                :$containerIndex
                :$containerWidth
                :$block
                :$blockData
                :$blockIndex
                :$loop
                :$occurrence
                :$pageSlot
            />
            <x-capell::interactions :triggers="$interactions" />
        </div>
    </x-capell::widgets.runtime-wrapper>
@elseif ($type === 'livewire')
    <x-capell::widgets.runtime-wrapper
        :settings="$presentation"
        :resource-public-ids="$resourcePublicIds"
    >
        <div id="{{ $blockDomId }}" class="layout-block">
            @livewire($blockComponent, ['blockReference' => $blockReference], key($containerKey . '-' . $block->key . '-' . $occurrence))
        </div>
        <x-capell::interactions :triggers="$interactions" />
    </x-capell::widgets.runtime-wrapper>
@endif
