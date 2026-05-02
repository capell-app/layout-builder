<?php
use Capell\Navigation\Data\NavigationItemData;

/**
 * @var NavigationItemData $item
 * @var bool $active
 */
?>

@props([
    'item',
    'active' => $item->active,
])
<li
    {{
        $attributes->class([
            'list-item',
            'active' => $active,
        ])
    }}
>
    <a
        href="{{ $item->data['url'] ?? '' }}"
        @class([
            'inline-block py-1',
            'hover:text-primary focus:text-primary' => ! $active,
            'text-primary font-semibold' => $active,
        ])
        wire:navigate
    >
        {{ $item->label }}
    </a>
    @if ($item->children->count() > 0)
        <x-capell::list class="ml-2" gap="gap-y-0.5">
            @foreach ($item->children as $child)
                <x-dynamic-component
                    :component="! empty($child->data['component']) ? $child->data['component'] : 'capell::list.item'"
                    :class="$attributes->get('class')"
                    :item="$child"
                />
            @endforeach
        </x-capell::list>
    @endif
</li>
