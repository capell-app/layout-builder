@props(['pageSlot', 'container' => null, 'containerKey' => null, 'containerWidth' => null, 'loop' => null, 'widget' => null])

<x-capell-mosaic::widget.wrapper
    class="widget-page-slot"
    :$container
    :$containerKey
    :$containerWidth
    :index="$loop?->index ?? 0"
    :$widget
>
    <div>
        {{ $pageSlot }}
    </div>
</x-capell-mosaic::widget.wrapper>
