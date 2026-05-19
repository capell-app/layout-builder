@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\LayoutBuilder\Support\Livewire\OpaqueBlockReference;

    $occurrence = $blockData['occurrence'] ?? 1;
@endphp

@if ($type === 'blade')
    <x-dynamic-component
        class="capell-layout-builder-layout-block"
        :component="$component"
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
@elseif ($type === 'livewire')
    @php
        $blockReference = OpaqueBlockReference::encode([
            'container_key' => $containerKey,
            'block_key' => $blockData['block_key'] ?? $block->key,
            'layout_id' => $layout?->getKey(),
            'language_id' => Frontend::language()?->getKey(),
            'occurrence' => $occurrence,
            'page_id' => Frontend::page()?->getKey(),
            'page_type' => Frontend::page()?->getMorphClass(),
            'site_id' => Frontend::site()?->getKey(),
            'block_data' => $blockData,
            'block_index' => $blockIndex,
        ]);
    @endphp

    @livewire($component, ['blockReference' => $blockReference], key($containerKey . '-' . $block->key . '-' . $occurrence))
@endif
