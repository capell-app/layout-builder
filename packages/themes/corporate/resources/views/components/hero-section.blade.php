@props([
    'eyebrow' => null,
    'title' => 'Built for serious businesses.',
    'subtitle' => null,
    'ctaLabel' => 'Get started',
    'ctaUrl' => '#contact',
    'secondaryCtaLabel' => 'Learn more',
    'secondaryCtaUrl' => '#features',
    'backgroundStyle' => 'gradient',
    'imageUrl' => null,
])

<section
    aria-label="Hero"
    class="relative overflow-hidden"
    style="
        background: @if ($backgroundStyle === 'gradient')
            linear-gradient(135deg,
            var(--color-primary),
            color-mix(in
            srgb,
            var(--color-primary)
            60%,
            var(--color-accent)));
        @elseif ($backgroundStyle === 'image' && $imageUrl)
            center
            /
            cover
            no-repeat
            url('{{ $imageUrl }}');
        @else
            var(--color-primary);
        @endif;
    "
>
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8 lg:py-32">
        <div class="max-w-3xl">
            @if ($eyebrow)
                <p
                    class="mb-4 text-sm font-semibold uppercase tracking-widest text-[var(--color-accent)]"
                >
                    {{ $eyebrow }}
                </p>
            @endif

            <h1
                class="text-4xl font-bold leading-tight text-white sm:text-5xl lg:text-6xl"
            >
                {{ $slot->isEmpty() ? $title : $slot }}
            </h1>

            @if ($subtitle)
                <p class="mt-6 max-w-2xl text-lg text-white/80">
                    {{ $subtitle }}
                </p>
            @endif

            <div class="mt-10 flex flex-wrap gap-4">
                <a
                    href="{{ $ctaUrl }}"
                    class="inline-flex items-center rounded-md bg-[var(--color-accent)] px-6 py-3 font-semibold text-[var(--color-accent-foreground)] shadow-sm transition hover:brightness-110"
                    aria-label="{{ $ctaLabel }}"
                >
                    {{ $ctaLabel }}
                </a>

                @if ($secondaryCtaLabel && $secondaryCtaUrl)
                    <a
                        href="{{ $secondaryCtaUrl }}"
                        class="inline-flex items-center rounded-md border border-white/30 px-6 py-3 font-semibold text-white transition hover:bg-white/10"
                    >
                        {{ $secondaryCtaLabel }}
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
