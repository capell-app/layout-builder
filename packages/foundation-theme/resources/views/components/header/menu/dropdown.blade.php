@props([
    'id' => null,
    'itemClass',
    'dropdownItemClass' => 'text-secondary hover:bg-primary/10 focus-visible:bg-primary/10 focus-visible:ring-primary/40 dark:text-secondary dark:hover:bg-primary/20 dark:focus-visible:bg-primary/20 flex w-full items-center px-4 py-2 text-left text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 disabled:cursor-not-allowed disabled:opacity-50 lg:px-3 lg:py-1.5',
    'dropdownName' => 'header-menu',
    'menu',
    'item',
])
@php
    use Capell\Navigation\Data\NavigationItemData;

    /** @var NavigationItemData $item */
    $currentDropdownName = $dropdownName . '-' . ($id !== null ? (string) $id : hash('sha256', $item->label));
@endphp

<x-capell::dropdown
    :name="$currentDropdownName"
    background="bg-white"
    class="max-lg:inset-0 max-lg:rounded-none max-lg:border-0 max-lg:shadow-none"
    container-tag="li"
    container-class="group flex lg:relative"
    panel-tag="ul"
    panel-click-outside="window.matchMedia('(min-width: {{ config('capell-frontend.breakpoints.lg') }}px)').matches ? close($refs['{{ $currentDropdownName }}_toggle']) : null"
    panel-hidden-class="invisible translate-x-full"
    panel-visible-class="visible translate-x-0"
    :stop-trigger-click-propagation="true"
    trigger-click="toggle()"
    trigger-type="button"
    :use-float="false"
    x-on:focusin.window="! $refs['{{ $currentDropdownName }}_dropdown'].contains($event.target) && close()"
    x-on:keydown.escape.prevent.stop="close($refs['{{ $currentDropdownName }}_toggle'])"
>
    <x-slot:trigger
        @class([
            $itemClass,
            'hover:text-primary focus:text-primary' => ! $item->active,
            'active text-primary' => $item->active,
            $item->data['class'] ?? '',
        ])
    >
        @if (! empty($item->data['icon']))
            <x-dynamic-component
                class="h-6 w-6"
                :component="$item->data['icon']"
            />
        @endif

        <span
            @class([
                'mr-1 lg:sr-only' => ! empty($item->data['hide_label']),
            ])
        >
            {{ $item->label }}
        </span>

        @svg('heroicon-o-chevron-right', '-mr-2 ml-auto h-4 w-4 text-gray-400 group-hover:text-inherit group-focus:text-inherit lg:rotate-90')
    </x-slot>

    <li
        class="nav-item-dropdown-header border-b border-gray-200 pb-1 lg:hidden dark:border-gray-700"
    >
        <button
            type="button"
            @class([
                $dropdownItemClass,
                'hover:text-primary focus:text-primary font-semibold',
            ])
            x-on:click="close($refs['{{ $currentDropdownName }}_toggle'])"
        >
            @svg('heroicon-o-arrow-left', 'mr-1 h-5 w-5 stroke-current')
            <span>
                {{ $item->label }}
            </span>
        </button>
    </li>

    @foreach ($item->children as $id => $child)
        @if ($child->children->count() > 0)
            @include('capell::components.header.menu.dropdown', [
                'id' => $id,
                'dropdownName' => $currentDropdownName,
                'item' => $child,
                'menu' => $menu,
                'index' => $loop->index,
            ])
        @else
            <li class="nav-item">
                <a
                    href="{{ $child->data['url'] ?? '' }}"
                    @if (!empty($child->data['target'])) target="{{ $child->data['target'] }}" @endif
                    wire:navigate
                    @class([
                        $dropdownItemClass,
                        'hover:text-primary focus:text-primary' => ! $child->active,
                        'active text-primary dark:text-primary' => $child->active,
                        $child->data['class'] ?? '',
                    ])
                >
                    <span>
                        {{ $child->label }}
                    </span>
                </a>
            </li>
        @endif
    @endforeach
</x-capell::dropdown>
