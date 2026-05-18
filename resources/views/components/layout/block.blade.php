@php
    use Capell\Frontend\Facades\Frontend;
    use Illuminate\Support\Facades\Crypt;

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
        $blockReference = Crypt::encryptString(json_encode([
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
        ], JSON_THROW_ON_ERROR));
    @endphp

    @livewire($component, ['blockReference' => $blockReference], key($containerKey . '-' . $block->key . '-' . $occurrence))
@endif
