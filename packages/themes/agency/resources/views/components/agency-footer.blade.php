@props([
    'wordmark' => 'Capell.',
    'tagline' => 'Let\'s make something worth looking at.',
    'ctaLabel' => 'Start a project',
    'ctaUrl' => '#inquiry',
    'copyright' => null,
    'socials' => [
        ['label' => 'Instagram', 'url' => '#'],
        ['label' => 'Dribbble', 'url' => '#'],
        ['label' => 'Behance', 'url' => '#'],
        ['label' => 'LinkedIn', 'url' => '#'],
    ],
])

<footer
    role="contentinfo"
    class="relative overflow-hidden bg-[var(--color-surface-dark)] text-[var(--color-surface-dark-fg)]"
>
    {{-- Decorative gradient wash --}}
    <div
        aria-hidden="true"
        class="pointer-events-none absolute inset-x-0 top-0 h-48"
        style="
            background: linear-gradient(
                180deg,
                color-mix(in srgb, var(--color-primary) 30%, transparent),
                transparent
            );
        "
    ></div>

    <div class="relative mx-auto max-w-[1440px] px-4 py-20 sm:px-6 lg:px-10">
        <div class="grid grid-cols-1 gap-12 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <p
                    class="agency-display text-6xl font-bold sm:text-7xl md:text-8xl lg:text-9xl"
                >
                    {{ $tagline }}
                </p>
                <a
                    href="{{ $ctaUrl }}"
                    class="mt-10 inline-flex items-center gap-2 rounded-full bg-[var(--color-primary)] px-8 py-4 text-base font-semibold text-[var(--color-primary-foreground)] transition hover:brightness-110"
                >
                    {{ $ctaLabel }}
                    <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <div class="lg:col-span-4">
                <p class="agency-display text-3xl font-bold">
                    {{ $wordmark }}
                </p>
                @if (! empty($socials))
                    <nav
                        aria-label="Social"
                        class="mt-8"
                    >
                        <ul
                            role="list"
                            class="space-y-3"
                        >
                            @foreach ($socials as $social)
                                <li>
                                    <a
                                        href="{{ $social['url'] ?? '#' }}"
                                        rel="noopener"
                                        class="text-[var(--color-surface-dark-fg)]/80 group inline-flex items-center gap-2 text-lg font-medium transition hover:text-[var(--color-primary)]"
                                    >
                                        {{ $social['label'] ?? '' }}
                                        <span
                                            aria-hidden="true"
                                            class="opacity-0 transition group-hover:translate-x-1 group-hover:opacity-100"
                                        >
                                            &rarr;
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
            </div>
        </div>

        <div
            class="text-[var(--color-surface-dark-fg)]/60 mt-16 flex flex-wrap items-center justify-between gap-4 border-t border-white/10 pt-8 text-sm"
        >
            <p>
                {{ $copyright ?? '(c) ' . date('Y') . ' Capell Studio. Made with opinions.' }}
            </p>
            {{ $slot ?? '' }}
        </div>
    </div>
</footer>
