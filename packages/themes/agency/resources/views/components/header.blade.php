@props([
    'brand' => 'Capell.',
    'links' => [
        ['label' => 'Work', 'url' => '#portfolio'],
        ['label' => 'Services', 'url' => '#services'],
        ['label' => 'Process', 'url' => '#process'],
        ['label' => 'Studio', 'url' => '#studio'],
        ['label' => 'Contact', 'url' => '#inquiry'],
    ],
])

<header role="banner" class="sticky top-0 z-40 border-b border-[var(--color-border)] bg-[var(--color-bg)]/85 backdrop-blur">
    <div class="mx-auto flex max-w-[1440px] items-center justify-between px-4 py-5 sm:px-6 lg:px-10">
        <a href="/" class="agency-display text-2xl font-bold text-[var(--color-fg)] hover:text-[var(--color-primary)] transition">
            {{ $brand }}
        </a>

        <nav role="navigation" aria-label="Primary" class="hidden md:block">
            <ul class="flex items-center gap-8 text-sm font-medium">
                @foreach ($links as $link)
                    <li>
                        <a href="{{ $link['url'] }}"
                           class="text-[var(--color-fg-muted)] transition hover:text-[var(--color-primary)]">
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="flex items-center gap-3">
            {{ $slot ?? '' }}
            <x-agency::dark-mode-toggle />
            <x-agency::language-switcher />
            <a href="#inquiry"
               class="hidden sm:inline-flex items-center rounded-full bg-[var(--color-primary)] px-5 py-2 text-sm font-semibold text-[var(--color-primary-foreground)] shadow-[var(--shadow-float)] transition hover:brightness-110">
                Start a project
            </a>
        </div>
    </div>
</header>
