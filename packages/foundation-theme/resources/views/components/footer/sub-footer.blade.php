@php
    use Capell\Frontend\Actions\GetLayoutContainerWidthAction;

    $containerWidth = GetLayoutContainerWidthAction::run();
@endphp

@props([
    'items' => [],
    'siteLanguages',
])
<div {{ $attributes }}>
    <div
        @class([
            'sm:grid-col-2 grid flex-wrap items-center gap-x-4 gap-y-4 py-4 md:flex lg:grid lg:grid-cols-3 lg:py-5 xl:grid-cols-5',
            $containerWidth->getContainerClass(),
        ])
    >
        <nav
            id="sub-footer-menu"
            class="flex flex-wrap items-center justify-center gap-y-2 md:justify-start lg:order-1 xl:col-span-2"
        >
            @foreach ($items as $id => $item)
                <a
                    href="{{ $item->data['url'] ?? '' }}"
                    @wireNavigate
                    @class([
                        'nav-item hover:text-primary flex text-sm font-medium text-[var(--color-footer-link)]',
                        "before:content-['|'] before:px-2 before:opacity-40 before:text-[var(--color-footer-muted)]" => ! $loop->first,
                        'active' => $item->active,
                    ])
                >
                    @if (! empty($item->data['icon']))
                        <x-dynamic-component
                            :component="$item->active ? ($item->data['active_icon'] ?? str_replace('heroicon-o-', 'heroicon-s-', $item->data['icon'])) : $item->data['icon']"
                            @class(['h-6 w-6', 'text-primary' => $item->active])
                        />
                    @endif

                    <span
                        @class([
                            'lg:sr-only' => ! empty($item->data['hide_label']),
                        ])
                    >
                        {{ $item->label }}
                    </span>
                </a>
            @endforeach
        </nav>

        @if (count($siteLanguages) > 1)
            <div
                class="grow text-center md:text-right lg:order-3 xl:col-span-2"
            >
                <x-capell::languages
                    :languages="$siteLanguages"
                    :dark-mode="true"
                    align="right"
                    class="mx-auto"
                />
            </div>
        @endif

        @if ($slot->isNotEmpty())
            <div
                class="sm:grid-col-2 grow text-center text-xs font-medium leading-tight text-[var(--color-footer-muted)] md:col-span-1 lg:order-2"
            >
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
