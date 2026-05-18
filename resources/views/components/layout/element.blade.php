@php
    use Capell\Frontend\Facades\Frontend;
    use Illuminate\Support\Facades\Crypt;

    $occurrence = $elementData['occurrence'] ?? 1;
@endphp

@if ($type === 'blade')
    <x-dynamic-component
        class="capell-layout-builder-layout-element"
        :component="$component"
        :$container
        :$containerColspan
        :$containerKey
        :$containerIndex
        :$containerWidth
        :$element
        :$elementData
        :$elementIndex
        :$loop
        :$occurrence
        :$pageSlot
    />
@elseif ($type === 'livewire')
    @php
        $elementReference = Crypt::encryptString(json_encode([
            'container_key' => $containerKey,
            'element_key' => $elementData['element_key'] ?? $element->key,
            'layout_id' => $layout?->getKey(),
            'language_id' => Frontend::language()?->getKey(),
            'occurrence' => $occurrence,
            'page_id' => Frontend::page()?->getKey(),
            'page_type' => Frontend::page()?->getMorphClass(),
            'site_id' => Frontend::site()?->getKey(),
            'element_data' => $elementData,
            'element_index' => $elementIndex,
        ], JSON_THROW_ON_ERROR));
    @endphp

    @livewire($component, ['elementReference' => $elementReference], key($containerKey . '-' . $element->key . '-' . $occurrence))
@endif
