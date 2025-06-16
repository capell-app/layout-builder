<?php

declare(strict_types=1);

?>

@props([
    'container' => '',
])
<x-capell::widget.wrapper
    class="widget-navigation-bar"
    :$container
    :$containerKey
    :index="$loop->index"
    :$widget
>
    <ul
        class="mb-20 mb-4 mt-10 flex flex-col flex-wrap items-end items-center gap-4 border-b border-gray-100 px-2 text-center text-sm font-medium text-gray-500 md:flex-row"
    >
        @foreach ($items as $item)
            <li class="-mb-px">
                <a
                    href="{{ $item['data']['url'] }}"
                    @class([
                        'hover:bg-primary inline-block rounded-t border-b-2 border-transparent px-4 py-3 hover:text-white',
                        'border-b-primary' => $item['active'] ?? false,
                    ])
                    wire:navigate
                >
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</x-capell::widget.wrapper>

<?php
