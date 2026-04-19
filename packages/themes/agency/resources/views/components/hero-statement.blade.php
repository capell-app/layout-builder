@props([
    'eyebrow' => null,
    'statement' => 'Brands worth the attention they demand.',
    'subtitle' => null,
    'ctaLabel' => 'Start a project',
    'ctaUrl' => '#inquiry',
    'secondaryCtaLabel' => 'See the work',
    'secondaryCtaUrl' => '#portfolio',
    'imageUrl' => null,
])

<section
    aria-label="Hero"
    class="relative overflow-hidden bg-[var(--color-surface-dark)] text-[var(--color-surface-dark-fg)]"
>
    {{-- Gradient mesh background --}}
    <div
        aria-hidden="true"
        class="pointer-events-none absolute inset-0"
        style="
            background:
                radial-gradient(
                    60rem 40rem at 85% 10%,
                    color-mix(in srgb, var(--color-primary) 55%, transparent),
                    transparent 60%
                ),
                radial-gradient(
                    50rem 30rem at 10% 80%,
                    color-mix(in srgb, var(--color-accent) 45%, transparent),
                    transparent 60%
                );
        "
    ></div>

    <div
        class="relative mx-auto grid max-w-[1440px] grid-cols-1 gap-12 px-4 py-24 sm:px-6 sm:py-32 lg:grid-cols-12 lg:px-10 lg:py-40"
    >
        <div class="lg:col-span-8">
            @if ($eyebrow)
                <p
                    class="text-[var(--color-surface-dark-fg)]/80 mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em]"
                >
                    <span
                        aria-hidden="true"
                        class="h-1.5 w-1.5 rounded-full bg-[var(--color-primary)]"
                    ></span>
                    {{ $eyebrow }}
                </p>
            @endif

            <h1
                class="agency-display text-5xl sm:text-6xl md:text-7xl lg:text-8xl xl:text-[9rem]"
            >
                {{ $slot->isEmpty() ? $statement : $slot }}
            </h1>

            @if ($subtitle)
                <p
                    class="text-[var(--color-surface-dark-fg)]/75 mt-10 max-w-2xl text-lg sm:text-xl"
                >
                    {{ $subtitle }}
                </p>
            @endif

            <div class="mt-12 flex flex-wrap gap-4">
                <a
                    href="{{ $ctaUrl }}"
                    class="inline-flex items-center gap-2 rounded-full bg-[var(--color-primary)] px-8 py-4 text-base font-semibold text-[var(--color-primary-foreground)] shadow-[var(--shadow-float)] transition hover:brightness-110"
                    aria-label="{{ $ctaLabel }}"
                >
                    {{ $ctaLabel }}
                    <span aria-hidden="true">&rarr;</span>
                </a>

                @if ($secondaryCtaLabel && $secondaryCtaUrl)
                    <a
                        href="{{ $secondaryCtaUrl }}"
                        class="inline-flex items-center rounded-full border border-white/30 px-8 py-4 text-base font-semibold text-[var(--color-surface-dark-fg)] transition hover:bg-white/10"
                    >
                        {{ $secondaryCtaLabel }}
                    </a>
                @endif
            </div>
        </div>

        @if ($imageUrl)
            <div class="self-end lg:col-span-4">
                <img
                    src="{{ $imageUrl }}"
                    alt=""
                    loading="eager"
                    class="w-full rounded-[var(--radius-lg)] object-cover shadow-2xl"
                />
            </div>
        @endif
    </div>
</section>
