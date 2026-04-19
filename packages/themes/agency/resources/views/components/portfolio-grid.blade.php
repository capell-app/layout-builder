@props([
    'title' => 'Selected work',
    'subtitle' => null,
    'filters' => [],
    'projects' => [],
])

<section
    id="portfolio"
    aria-labelledby="portfolio-title"
    class="bg-[var(--color-bg)] py-[var(--section-y,5rem)]"
>
    <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-10">
        <header class="mb-12 flex flex-wrap items-end justify-between gap-6">
            <div class="max-w-2xl">
                <h2
                    id="portfolio-title"
                    class="agency-display text-4xl sm:text-5xl md:text-6xl"
                >
                    {{ $title }}
                </h2>
                @if ($subtitle)
                    <p class="mt-4 text-lg text-[var(--color-fg-muted)]">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>

            @if (! empty($filters))
                <div
                    role="tablist"
                    aria-label="Portfolio filters"
                    class="flex flex-wrap gap-2"
                >
                    @foreach ($filters as $filter)
                        <button
                            type="button"
                            role="tab"
                            data-portfolio-filter="{{ $filter['key'] ?? 'all' }}"
                            class="rounded-full border border-[var(--color-border)] bg-[var(--color-bg)] px-4 py-2 text-sm font-medium text-[var(--color-fg-muted)] transition hover:border-[var(--color-primary)] hover:text-[var(--color-primary)] aria-selected:bg-[var(--color-primary)] aria-selected:text-[var(--color-primary-foreground)]"
                            aria-selected="{{ ($filter['key'] ?? '') === 'all' ? 'true' : 'false' }}"
                        >
                            {{ $filter['label'] ?? '' }}
                        </button>
                    @endforeach
                </div>
            @endif
        </header>

        <ul
            role="list"
            class="grid grid-cols-1 gap-6 sm:grid-cols-6 lg:grid-cols-12"
        >
            @forelse ($projects as $project)
                @php
                    $size = $project['size'] ?? 'medium';
                    $span = match ($size) {
                        'large' => 'sm:col-span-6 lg:col-span-8',
                        'small' => 'sm:col-span-3 lg:col-span-4',
                        default => 'sm:col-span-3 lg:col-span-6',
                    };
                @endphp

                <li
                    class="{{ $span }}"
                    data-category="{{ $project['category'] ?? 'all' }}"
                >
                    <a
                        href="{{ $project['url'] ?? '#' }}"
                        class="group block overflow-hidden rounded-[var(--radius-lg)] bg-[var(--color-bg-muted)] transition hover:shadow-[var(--shadow-card)]"
                    >
                        <div
                            class="relative aspect-[4/3] w-full overflow-hidden"
                        >
                            @if (! empty($project['image']))
                                <img
                                    src="{{ $project['image'] }}"
                                    alt="{{ $project['client'] ?? '' }} — {{ $project['title'] ?? '' }}"
                                    loading="lazy"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                />
                            @else
                                <div
                                    aria-hidden="true"
                                    class="h-full w-full"
                                    style="
                                        background: linear-gradient(
                                            135deg,
                                            var(--color-primary),
                                            var(--color-accent)
                                        );
                                    "
                                ></div>
                            @endif
                        </div>
                        <div
                            class="flex items-center justify-between gap-4 p-6"
                        >
                            <div>
                                <p
                                    class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--color-primary)]"
                                >
                                    {{ $project['client'] ?? 'Project' }}
                                </p>
                                <h3
                                    class="mt-1 text-xl font-semibold leading-snug text-[var(--color-fg)]"
                                >
                                    {{ $project['title'] ?? '' }}
                                </h3>
                            </div>
                            <span
                                aria-hidden="true"
                                class="shrink-0 text-2xl text-[var(--color-fg-muted)] transition group-hover:translate-x-1 group-hover:text-[var(--color-primary)]"
                            >
                                &rarr;
                            </span>
                        </div>
                    </a>
                </li>
            @empty
                <li
                    class="col-span-full text-center text-[var(--color-fg-muted)]"
                >
                    {{ $slot->isEmpty() ? 'Portfolio coming soon.' : $slot }}
                </li>
            @endforelse
        </ul>
    </div>
</section>
