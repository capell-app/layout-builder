@props([
    'title' => 'Recognition',
    'subtitle' => null,
    'awards' => [],
])

<section
    aria-labelledby="awards-title"
    class="bg-[var(--color-bg)] py-[var(--section-y,5rem)]"
>
    <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-10">
        <header class="mx-auto mb-12 max-w-2xl text-center">
            <h2
                id="awards-title"
                class="agency-display text-4xl sm:text-5xl"
            >
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-4 text-lg text-[var(--color-fg-muted)]">
                    {{ $subtitle }}
                </p>
            @endif
        </header>

        <ul
            role="list"
            class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4"
        >
            @forelse ($awards as $award)
                <li
                    class="relative flex flex-col items-center gap-3 rounded-[var(--radius-lg)] border border-[var(--color-border)] bg-[var(--color-bg-muted)] p-6 text-center transition hover:border-[var(--color-primary)]"
                >
                    <span
                        aria-hidden="true"
                        class="bg-[var(--color-primary)]/10 flex h-14 w-14 items-center justify-center rounded-full text-2xl"
                    >
                        &#127942;
                    </span>
                    <p
                        class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--color-accent)]"
                    >
                        {{ $award['organizer'] ?? '' }}
                    </p>
                    <p class="text-base font-semibold">
                        {{ $award['name'] ?? '' }}
                    </p>
                    @if (! empty($award['year']))
                        <p class="text-sm text-[var(--color-fg-muted)]">
                            {{ $award['year'] }}
                        </p>
                    @endif
                </li>
            @empty
                <li
                    class="col-span-full text-center text-[var(--color-fg-muted)]"
                >
                    {{ $slot ?? 'No awards yet.' }}
                </li>
            @endforelse
        </ul>
    </div>
</section>
