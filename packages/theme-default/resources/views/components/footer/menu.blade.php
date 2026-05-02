@props([
    'items' => null,
    'headingClass' => '',
    'menuItemClass' => 'focus:text-primary break-all text-sm font-medium leading-tight text-gray-100 hover:text-gray-400 xl:text-base',
    'menuSubItemClass' => 'focus:text-primary py-1 text-xs font-medium leading-tight text-gray-100 hover:text-gray-400 xl:text-sm',
])
@php
    use Capell\Navigation\Data\NavigationItemData;
    use Illuminate\Support\Collection;

    /**
     * @var Collection<NavigationItemData> $items
     */
    $half = (int) ceil($items->count() / 2);

    /**
     * @var Collection<Collection<NavigationItemData>> $chunks
     */
    $chunks = $items->chunk($half);
@endphp

<nav {{ $attributes->merge(['id' => 'footer-menu']) }}>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach ($chunks as $chunk)
            <ul class="flex flex-col gap-y-4">
                @foreach ($chunk as $id => $item)
                    <li
                        @class([
                            'nav-item',
                            'active' => $item->active,
                        ])
                    >
                        <a
                            href="{{ $item->data['url'] ?? '' }}"
                            wire:navigate
                            class="{{ $menuItemClass }}"
                        >
                            {{ $item->label }}
                        </a>
                        @if ($item->children->count() > 0)
                            <ul class="mt-2 flex flex-col gap-y-1">
                                @foreach ($item->children as $child)
                                    <li
                                        class="nav-child-item before:content-['-']"
                                    >
                                        <a
                                            href="{{ $child->data['url'] ?? '' }}"
                                            wire:navigate
                                            @class([
                                                $menuSubItemClass,
                                                'active' => $child->active,
                                            ])
                                        >
                                            {{ $child->label }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>
</nav>
