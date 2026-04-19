@props([
    'title' => 'Connects with your stack',
    'subtitle' => '100+ integrations and a powerful API.',
    'columns' => '6',
    'integrations' => [],
])

@php
    $cols = (int) $columns;
    $gridCols = match ($cols) {
        4 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4',
        5 => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-5',
        default => 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6',
    };
@endphp

<section
    id="integrations"
    aria-labelledby="integrations-title"
    class="border-y border-[var(--color-border)] bg-[var(--color-bg)] py-[var(--section-y,5rem)]"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mx-auto mb-10 max-w-2xl text-center">
            <h2
                id="integrations-title"
                class="text-2xl font-bold tracking-tight sm:text-3xl"
            >
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-3 text-[var(--color-fg-muted)]">
                    {{ $subtitle }}
                </p>
            @endif
        </header>

        <ul
            role="list"
            class="{{ $gridCols }} grid gap-4"
        >
            @forelse ($integrations as $integration)
                <li>
                    <a
                        href="{{ $integration['url'] ?? '#' }}"
                        class="flex h-20 items-center justify-center gap-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-muted)] px-4 text-center transition hover:border-[var(--color-primary)] hover:shadow-[var(--shadow-card)]"
                    >
                        @if (! empty($integration['logo_url']))
                            <img
                                src="{{ $integration['logo_url'] }}"
                                alt="{{ $integration['name'] ?? '' }} logo"
                                loading="lazy"
                                class="h-8 w-auto"
                            />
                        @else
                            <span
                                class="text-sm font-semibold text-[var(--color-fg)]"
                            >
                                {{ $integration['name'] ?? '' }}
                            </span>
                        @endif
                    </a>
                </li>
            @empty
                <li
                    class="col-span-full text-center text-[var(--color-fg-muted)]"
                >
                    No integrations configured.
                </li>
            @endforelse
        </ul>
    </div>
</section>
