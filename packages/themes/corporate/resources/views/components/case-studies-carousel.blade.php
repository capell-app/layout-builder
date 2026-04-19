@props([
    'title' => 'Case studies',
    'subtitle' => null,
    'studies' => [],
])

<section id="case-studies"
         aria-labelledby="case-studies-title"
         class="bg-[var(--color-bg)] py-[var(--section-y,4rem)]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mb-10 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 id="case-studies-title" class="text-3xl font-bold sm:text-4xl">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-2 text-[var(--color-fg-muted)]">{{ $subtitle }}</p>
                @endif
            </div>
        </header>

        <div role="region"
             aria-label="Case studies carousel"
             tabindex="0"
             class="-mx-4 flex snap-x snap-mandatory gap-6 overflow-x-auto px-4 pb-4 focus:outline-none focus-visible:outline-2">
            @forelse ($studies as $study)
                <article class="w-80 shrink-0 snap-start rounded-lg border border-[var(--color-border)] bg-[var(--color-bg-muted)] p-6 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-widest text-[var(--color-accent)]">
                        {{ $study['client'] ?? 'Client' }}
                    </p>
                    <h3 class="mt-2 text-xl font-semibold leading-snug">
                        <a href="{{ $study['url'] ?? '#' }}"
                           class="hover:text-[var(--color-primary)]">
                            {{ $study['headline'] ?? '' }}
                        </a>
                    </h3>
                    <p class="mt-3 text-sm text-[var(--color-fg-muted)]">
                        {{ $study['summary'] ?? '' }}
                    </p>
                </article>
            @empty
                <p class="text-[var(--color-fg-muted)]">{{ $slot ?? 'No case studies yet.' }}</p>
            @endforelse
        </div>
    </div>
</section>
