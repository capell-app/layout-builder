@props([
    'brand' => 'Capell',
    'links' => [
        ['label' => 'Features', 'url' => '#features'],
        ['label' => 'Integrations', 'url' => '#integrations'],
        ['label' => 'Pricing', 'url' => '#pricing'],
        ['label' => 'Customers', 'url' => '#testimonials'],
        ['label' => 'Docs', 'url' => '/docs'],
    ],
    'ctaLabel' => 'Start free',
    'ctaUrl' => '#signup',
    'signInUrl' => '/login',
])

<header role="banner"
        class="sticky top-0 z-40 border-b border-[var(--color-border)] bg-[var(--color-bg)]/85 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="/" class="flex items-center gap-2 text-lg font-bold tracking-tight text-[var(--color-fg)]">
            <span class="inline-block h-7 w-7 rounded-md" style="background: var(--gradient-cta);" aria-hidden="true"></span>
            {{ $brand }}
        </a>

        <nav role="navigation" aria-label="Primary" class="hidden md:block">
            <ul class="flex items-center gap-7 text-sm font-medium">
                @foreach ($links as $link)
                    <li>
                        <a href="{{ $link['url'] }}"
                           class="text-[var(--color-fg-muted)] transition hover:text-[var(--color-fg)]">
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex items-center gap-3">
            {{ $slot ?? '' }}
            <a href="{{ $signInUrl }}"
               class="hidden text-sm font-medium text-[var(--color-fg-muted)] transition hover:text-[var(--color-fg)] md:inline-block">
                Sign in
            </a>
            <a href="{{ $ctaUrl }}"
               class="inline-flex items-center rounded-lg bg-[var(--color-primary)] px-4 py-2 text-sm font-semibold text-[var(--color-primary-foreground)] shadow-sm transition hover:brightness-110">
                {{ $ctaLabel }}
            </a>
            <x-saas::dark-mode-toggle />
        </div>
    </div>
</header>
