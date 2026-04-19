@props([
    'items' => [],
])

@if (! empty($items))
    <nav
        aria-label="Breadcrumb"
        class="py-3"
    >
        <ol
            class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 text-sm text-[var(--color-fg-muted)] sm:px-6 lg:px-8"
        >
            @foreach ($items as $i => $item)
                <li class="flex items-center gap-2">
                    @if ($i > 0)
                        <span
                            aria-hidden="true"
                            class="text-[var(--color-fg-subtle)]"
                        >
                            /
                        </span>
                    @endif

                    @if ($i === count($items) - 1)
                        <span
                            aria-current="page"
                            class="font-medium text-[var(--color-fg)]"
                        >
                            {{ $item['name'] ?? '' }}
                        </span>
                    @else
                        <a
                            href="{{ $item['url'] ?? '#' }}"
                            class="transition hover:text-[var(--color-fg)]"
                        >
                            {{ $item['name'] ?? '' }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ol>
        {{ $slot ?? '' }}
    </nav>
@endif
