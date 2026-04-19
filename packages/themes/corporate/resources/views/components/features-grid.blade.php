@props([
    'title' => 'Why Capell',
    'subtitle' => null,
    'columns' => 3,
    'features' => [],
])

@php
    $cols = (int) $columns;
    $gridCols = match ($cols) {
        2 => 'sm:grid-cols-2',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
        default => 'sm:grid-cols-2 lg:grid-cols-3',
    };
@endphp

<section id="features"
         aria-labelledby="features-title"
         class="bg-[var(--color-bg)] py-[var(--section-y,4rem)]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mx-auto mb-12 max-w-2xl text-center">
            <h2 id="features-title" class="text-3xl font-bold sm:text-4xl">{{ $title }}</h2>
            @if ($subtitle)
                <p class="mt-4 text-[var(--color-fg-muted)]">{{ $subtitle }}</p>
            @endif
        </header>

        <ul role="list" class="grid grid-cols-1 gap-8 {{ $gridCols }}">
            @forelse ($features as $feature)
                <li class="rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-muted)] p-6 transition hover:shadow-md dark:bg-[var(--color-bg-muted)]">
                    <div class="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-md bg-[var(--color-primary)]/10 text-[var(--color-primary)]"
                         aria-hidden="true">
                        <span class="text-xl">*</span>
                    </div>
                    <h3 class="text-lg font-semibold">{{ $feature['title'] ?? '' }}</h3>
                    <p class="mt-2 text-sm text-[var(--color-fg-muted)]">
                        {{ $feature['description'] ?? '' }}
                    </p>
                </li>
            @empty
                <li class="col-span-full text-center text-[var(--color-fg-muted)]">
                    {{ $slot->isEmpty() ? 'No features configured.' : $slot }}
                </li>
            @endforelse
        </ul>
    </div>
</section>
