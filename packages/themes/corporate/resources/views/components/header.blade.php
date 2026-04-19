@props([
    'brand' => 'Capell',
    'links' => [
        ['label' => 'Features', 'url' => '#features'],
        ['label' => 'Case studies', 'url' => '#case-studies'],
        ['label' => 'Blog', 'url' => '#blog'],
        ['label' => 'Contact', 'url' => '#contact'],
    ],
])

<header
    role="banner"
    class="bg-[var(--color-bg)]/90 sticky top-0 z-40 border-b border-[var(--color-border)] backdrop-blur"
>
    <div
        class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8"
    >
        <a
            href="/"
            class="flex items-center gap-2 text-lg font-semibold text-[var(--color-primary)] dark:text-[var(--color-primary-foreground)]"
        >
            {{ $brand }}
        </a>

        <nav
            role="navigation"
            aria-label="Primary"
            class="hidden md:block"
        >
            <ul class="flex items-center gap-6 text-sm">
                @foreach ($links as $link)
                    <li>
                        <a
                            href="{{ $link['url'] }}"
                            class="text-[var(--color-fg-muted)] transition hover:text-[var(--color-fg)]"
                        >
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex items-center gap-3">
            {{ $slot ?? '' }}
            <x-corporate::dark-mode-toggle />
            <x-corporate::language-switcher />
        </div>
    </div>
</header>
