<?php

declare(strict_types=1);
?>

@props([
    'item' => [],
    'itemClass',
])
@php
    use Capell\Core\Data\NavigationItemData;

    /**
     * @var NavigationItemData $item
     */
@endphp

<li class="flex">
    <a
        href="{{ $item->data['url'] ?? '' }}"
        wire:navigate
        @class([
            $itemClass,
            'color-header hover:text-primary focus:text-primary' => ! $item->active,
            'active text-primary' => $item->active,
            $item->data['class'] ?? '',
        ])
        @if (!empty($item->data['target'])) target="{{ $item->data['target'] }}" @endif
    >
        <span
            @class([
                'lg:order-2',
                'lg:sr-only' => ! empty($item->data['hide_label']),
            ])
        >
            {{ $item->label }}
        </span>

        @if (! empty($item->data['icon']))
            <x-dynamic-component
                :component="$item->active ? ($item->data['active_icon'] ?? str_replace('heroicon-o-', 'heroicon-s-', $item->data['icon'])) : $item->data['icon']"
                @class(['h-4 w-4 lg:order-1', 'text-primary' => $item->active])
            />
        @endif
    </a>
</li>
