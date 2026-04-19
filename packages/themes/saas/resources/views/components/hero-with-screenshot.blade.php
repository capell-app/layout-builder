@props([
    'eyebrow' => 'Now in public beta',
    'title' => 'Ship faster. Scale without friction.',
    'subtitle' => 'The all-in-one platform teams love — built for modern product development.',
    'primaryCtaLabel' => 'Start free trial',
    'primaryCtaUrl' => '#signup',
    'secondaryCtaLabel' => 'Watch demo',
    'secondaryCtaUrl' => '#demo',
    'screenshotUrl' => null,
    'screenshotAlt' => 'Product screenshot',
    'trustBadges' => ['SOC 2 Type II', 'GDPR Ready', '99.99% uptime'],
])

<section
    id="hero"
    aria-label="Hero"
    class="relative overflow-hidden bg-[var(--color-bg)]"
>
    <div
        aria-hidden="true"
        class="pointer-events-none absolute inset-0 -z-10"
        style="
            background:
                radial-gradient(
                    at 15% 10%,
                    rgba(99, 102, 241, 0.15),
                    transparent 50%
                ),
                radial-gradient(
                    at 85% 20%,
                    rgba(16, 185, 129, 0.12),
                    transparent 50%
                ),
                radial-gradient(
                    at 50% 100%,
                    rgba(139, 92, 246, 0.1),
                    transparent 60%
                );
        "
    ></div>

    <div
        class="mx-auto max-w-7xl px-4 pb-20 pt-16 sm:px-6 sm:pb-28 sm:pt-24 lg:px-8 lg:pb-32 lg:pt-28"
    >
        <div class="mx-auto max-w-3xl text-center">
            @if ($eyebrow)
                <p
                    class="mb-6 inline-flex items-center gap-2 rounded-full border border-[var(--color-border)] bg-[var(--color-bg-muted)] px-3 py-1 text-xs font-semibold uppercase tracking-wider text-[var(--color-primary)]"
                >
                    <span
                        aria-hidden="true"
                        class="h-1.5 w-1.5 rounded-full bg-[var(--color-accent)]"
                    ></span>
                    {{ $eyebrow }}
                </p>
            @endif

            <h1
                class="text-4xl font-bold leading-tight tracking-tighter text-[var(--color-fg)] sm:text-5xl lg:text-6xl"
            >
                <span class="gradient-text">
                    {{ $slot->isEmpty() ? $title : $slot }}
                </span>
            </h1>

            @if ($subtitle)
                <p
                    class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-[var(--color-fg-muted)]"
                >
                    {{ $subtitle }}
                </p>
            @endif

            <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                <a
                    href="{{ $primaryCtaUrl }}"
                    class="inline-flex items-center justify-center rounded-lg bg-[var(--color-primary)] px-6 py-3 font-semibold text-[var(--color-primary-foreground)] shadow-[var(--shadow-elevated)] transition hover:brightness-110"
                    aria-label="{{ $primaryCtaLabel }}"
                >
                    {{ $primaryCtaLabel }}
                    <span
                        aria-hidden="true"
                        class="ml-2"
                    >
                        &rarr;
                    </span>
                </a>

                @if ($secondaryCtaLabel && $secondaryCtaUrl)
                    <a
                        href="{{ $secondaryCtaUrl }}"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-[var(--color-border)] bg-[var(--color-bg)] px-6 py-3 font-semibold text-[var(--color-fg)] transition hover:bg-[var(--color-bg-muted)]"
                    >
                        <span aria-hidden="true">&#9654;</span>
                        {{ $secondaryCtaLabel }}
                    </a>
                @endif
            </div>

            @if (! empty($trustBadges))
                <ul
                    role="list"
                    class="mt-8 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-xs font-medium text-[var(--color-fg-subtle)]"
                >
                    @foreach ($trustBadges as $badge)
                        <li class="flex items-center gap-1.5">
                            <span
                                aria-hidden="true"
                                class="saas-check text-[0.65rem]"
                            >
                                &#10003;
                            </span>
                            {{ is_array($badge) ? ($badge['label'] ?? '') : $badge }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Product screenshot mockup --}}
        <div class="mt-16 flex justify-center sm:mt-20">
            <figure class="relative w-full max-w-5xl">
                <div
                    class="rounded-xl border border-[var(--color-border)] bg-[var(--color-bg-muted)] p-2 shadow-[var(--shadow-screenshot)]"
                >
                    <div class="flex items-center gap-1.5 px-2 py-2">
                        <span
                            class="h-3 w-3 rounded-full bg-red-400/70"
                            aria-hidden="true"
                        ></span>
                        <span
                            class="h-3 w-3 rounded-full bg-yellow-400/70"
                            aria-hidden="true"
                        ></span>
                        <span
                            class="h-3 w-3 rounded-full bg-green-400/70"
                            aria-hidden="true"
                        ></span>
                    </div>

                    @if ($screenshotUrl)
                        <img
                            src="{{ $screenshotUrl }}"
                            alt="{{ $screenshotAlt }}"
                            loading="lazy"
                            class="w-full rounded-lg"
                        />
                    @else
                        <div
                            class="aspect-[16/9] w-full rounded-lg bg-[var(--color-bg)]"
                            role="img"
                            aria-label="{{ $screenshotAlt }}"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--color-primary-soft) 0%,
                                    var(--color-accent-soft) 100%
                                );
                            "
                        ></div>
                    @endif
                </div>
                <figcaption class="sr-only">{{ $screenshotAlt }}</figcaption>
            </figure>
        </div>
    </div>
</section>
