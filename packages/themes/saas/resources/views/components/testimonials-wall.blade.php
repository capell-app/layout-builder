@props([
    'title' => 'Loved by teams worldwide',
    'subtitle' => null,
    'columns' => '3',
    'testimonials' => [],
])

@php
    $cols = (int) $columns;
    $gridCols = match ($cols) {
        2 => 'sm:columns-2',
        4 => 'sm:columns-2 lg:columns-4',
        default => 'sm:columns-2 lg:columns-3',
    };
@endphp

<section
    id="testimonials"
    aria-labelledby="testimonials-title"
    class="bg-[var(--color-bg)] py-[var(--section-y,5rem)]"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mx-auto mb-12 max-w-2xl text-center">
            <h2
                id="testimonials-title"
                class="text-3xl font-bold tracking-tight sm:text-4xl"
            >
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-4 text-[var(--color-fg-muted)]">
                    {{ $subtitle }}
                </p>
            @endif
        </header>

        @if (empty($testimonials))
            <p class="text-center text-[var(--color-fg-muted)]">
                No testimonials configured.
            </p>
        @else
            <div class="{{ $gridCols }} gap-6 [column-fill:_balance]">
                @foreach ($testimonials as $t)
                    <figure
                        class="mb-6 break-inside-avoid rounded-xl border border-[var(--color-border)] bg-[var(--color-bg-muted)] p-6 shadow-[var(--shadow-card)]"
                    >
                        @if (! empty($t['rating']))
                            <div
                                class="mb-3 flex items-center gap-0.5 text-[var(--color-accent)]"
                                aria-label="Rating: {{ $t['rating'] }} out of 5"
                            >
                                @for ($i = 0; $i < (int) $t['rating']; $i++)
                                    <span aria-hidden="true">&#9733;</span>
                                @endfor
                            </div>
                        @endif

                        <blockquote class="text-[var(--color-fg)]">
                            <p class="text-base leading-relaxed">
                                &ldquo;{{ $t['quote'] ?? '' }}&rdquo;
                            </p>
                        </blockquote>

                        <figcaption class="mt-5 flex items-center gap-3">
                            @if (! empty($t['avatar_url']))
                                <img
                                    src="{{ $t['avatar_url'] }}"
                                    alt=""
                                    loading="lazy"
                                    class="h-10 w-10 rounded-full object-cover"
                                />
                            @else
                                <span
                                    aria-hidden="true"
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-[var(--color-primary-soft)] text-sm font-semibold text-[var(--color-primary)]"
                                >
                                    {{ strtoupper(substr($t['author'] ?? '?', 0, 1)) }}
                                </span>
                            @endif
                            <div>
                                <div
                                    class="text-sm font-semibold text-[var(--color-fg)]"
                                >
                                    {{ $t['author'] ?? '' }}
                                </div>
                                <div
                                    class="text-xs text-[var(--color-fg-muted)]"
                                >
                                    {{ trim(($t['role'] ?? '') . (! empty($t['role']) && ! empty($t['company']) ? ' · ' : '') . ($t['company'] ?? '')) }}
                                </div>
                            </div>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        @endif
    </div>
</section>
