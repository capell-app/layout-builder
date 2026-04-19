@props([
    'title' => 'How we work',
    'subtitle' => null,
    'steps' => [],
])

<section
    id="process"
    aria-labelledby="process-title"
    class="bg-[var(--color-bg-muted)] py-[var(--section-y,5rem)]"
>
    <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-10">
        <header class="mx-auto mb-14 max-w-2xl text-center">
            <h2
                id="process-title"
                class="agency-display text-4xl sm:text-5xl md:text-6xl"
            >
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-4 text-lg text-[var(--color-fg-muted)]">
                    {{ $subtitle }}
                </p>
            @endif
        </header>

        <ol
            role="list"
            class="relative grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4"
        >
            {{-- Decorative connector line --}}
            <span
                aria-hidden="true"
                class="pointer-events-none absolute left-0 right-0 top-[4.5rem] hidden h-px bg-gradient-to-r from-transparent via-[var(--color-primary)] to-transparent lg:block"
            ></span>

            @foreach ($steps as $i => $step)
                <li
                    class="relative rounded-[var(--radius-lg)] border border-[var(--color-border)] bg-[var(--color-bg)] p-8 shadow-sm"
                >
                    <div
                        aria-hidden="true"
                        class="mb-6 inline-flex h-16 w-16 items-center justify-center rounded-full bg-[var(--color-primary)] text-[var(--color-primary-foreground)]"
                    >
                        <span class="agency-display text-2xl">
                            {{ $step['number'] ?? sprintf('%02d', $i + 1) }}
                        </span>
                    </div>
                    <h3 class="text-2xl font-semibold tracking-tight">
                        {{ $step['title'] ?? '' }}
                    </h3>
                    @if (! empty($step['description']))
                        <p class="mt-3 text-[var(--color-fg-muted)]">
                            {{ $step['description'] }}
                        </p>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
