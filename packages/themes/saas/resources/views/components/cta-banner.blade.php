@props([
    'title' => 'Ready to ship faster?',
    'subtitle' => 'Join 10,000+ teams already building with Capell.',
    'primaryCtaLabel' => 'Start free trial',
    'primaryCtaUrl' => '#signup',
    'secondaryCtaLabel' => 'Talk to sales',
    'secondaryCtaUrl' => '#contact',
    'variant' => 'gradient',
])

@php
    $bg = match ($variant) {
        'solid' => 'background: var(--color-primary);',
        'inverse' => 'background: var(--color-bg-subtle);',
        default => 'background: var(--gradient-cta);',
    };
    $fg = $variant === 'inverse' ? 'text-[var(--color-fg)]' : 'text-white';
    $subtleFg = $variant === 'inverse' ? 'text-[var(--color-fg-muted)]' : 'text-white/85';
@endphp

<section
    id="cta"
    aria-labelledby="cta-banner-title"
    class="py-[var(--section-y,5rem)]"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div
            class="relative overflow-hidden rounded-2xl px-6 py-14 shadow-[var(--shadow-elevated)] sm:px-12 sm:py-20"
            style="{{ $bg }}"
        >
            <div class="relative z-10 mx-auto max-w-2xl text-center">
                <h2
                    id="cta-banner-title"
                    class="{{ $fg }} text-3xl font-bold tracking-tight sm:text-4xl"
                >
                    {{ $slot->isEmpty() ? $title : $slot }}
                </h2>
                @if ($subtitle)
                    <p class="{{ $subtleFg }} mt-4 text-lg">
                        {{ $subtitle }}
                    </p>
                @endif

                <div
                    class="mt-8 flex flex-wrap items-center justify-center gap-3"
                >
                    <a
                        href="{{ $primaryCtaUrl }}"
                        class="inline-flex items-center rounded-lg bg-white px-6 py-3 font-semibold text-[var(--color-primary)] shadow-sm transition hover:brightness-95"
                        aria-label="{{ $primaryCtaLabel }}"
                    >
                        {{ $primaryCtaLabel }}
                    </a>
                    @if ($secondaryCtaLabel && $secondaryCtaUrl)
                        <a
                            href="{{ $secondaryCtaUrl }}"
                            class="{{ $fg }} inline-flex items-center rounded-lg border border-white/30 px-6 py-3 font-semibold transition hover:bg-white/10"
                        >
                            {{ $secondaryCtaLabel }}
                        </a>
                    @endif
                </div>
            </div>

            @if ($variant === 'gradient')
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-0"
                    style="
                        background:
                            radial-gradient(
                                circle at 20% 20%,
                                rgba(255, 255, 255, 0.15),
                                transparent 40%
                            ),
                            radial-gradient(
                                circle at 80% 80%,
                                rgba(16, 185, 129, 0.18),
                                transparent 50%
                            );
                    "
                ></div>
            @endif
        </div>
    </div>
</section>
