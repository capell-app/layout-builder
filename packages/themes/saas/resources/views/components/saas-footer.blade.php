@props([
    'brand' => 'Capell',
    'tagline' => 'The all-in-one platform for modern product teams.',
    'copyright' => null,
    'showNewsletter' => 'yes',
    'columns' => [
        ['heading' => 'Product', 'links' => [
            ['label' => 'Features', 'url' => '#features'],
            ['label' => 'Pricing', 'url' => '#pricing'],
            ['label' => 'Integrations', 'url' => '#integrations'],
            ['label' => 'Changelog', 'url' => '/changelog'],
        ]],
        ['heading' => 'Company', 'links' => [
            ['label' => 'About', 'url' => '/about'],
            ['label' => 'Customers', 'url' => '/customers'],
            ['label' => 'Careers', 'url' => '/careers'],
            ['label' => 'Contact', 'url' => '/contact'],
        ]],
        ['heading' => 'Resources', 'links' => [
            ['label' => 'Documentation', 'url' => '/docs'],
            ['label' => 'API reference', 'url' => '/api'],
            ['label' => 'Blog', 'url' => '/blog'],
            ['label' => 'Community', 'url' => '/community'],
        ]],
        ['heading' => 'Legal', 'links' => [
            ['label' => 'Privacy policy', 'url' => '/privacy'],
            ['label' => 'Terms of service', 'url' => '/terms'],
            ['label' => 'Security', 'url' => '/security'],
            ['label' => 'DPA', 'url' => '/dpa'],
        ]],
    ],
    'socials' => [
        ['label' => 'Twitter', 'url' => '#'],
        ['label' => 'LinkedIn', 'url' => '#'],
        ['label' => 'GitHub', 'url' => '#'],
        ['label' => 'YouTube', 'url' => '#'],
    ],
])

<footer
    role="contentinfo"
    class="border-t border-[var(--color-border)] bg-[var(--color-bg-muted)]"
>
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-6">
            {{-- Brand + newsletter block --}}
            <div class="lg:col-span-2">
                <a
                    href="/"
                    class="flex items-center gap-2 text-lg font-bold tracking-tight text-[var(--color-fg)]"
                >
                    <span
                        class="inline-block h-7 w-7 rounded-md"
                        style="background: var(--gradient-cta)"
                        aria-hidden="true"
                    ></span>
                    {{ $brand }}
                </a>
                <p class="mt-3 max-w-xs text-sm text-[var(--color-fg-muted)]">
                    {{ $tagline }}
                </p>

                @if ($showNewsletter === 'yes')
                    <form
                        class="mt-6 max-w-sm"
                        aria-label="Newsletter signup"
                        action="#"
                        method="post"
                    >
                        <label
                            for="saas-footer-newsletter"
                            class="text-xs font-semibold uppercase tracking-widest text-[var(--color-fg-muted)]"
                        >
                            Get product updates
                        </label>
                        <div class="mt-2 flex gap-2">
                            <input
                                id="saas-footer-newsletter"
                                type="email"
                                name="email"
                                required
                                autocomplete="email"
                                placeholder="you@company.com"
                                class="block w-full rounded-lg border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2 text-sm text-[var(--color-fg)] placeholder:text-[var(--color-fg-subtle)]"
                            />
                            <button
                                type="submit"
                                class="shrink-0 rounded-lg bg-[var(--color-primary)] px-4 py-2 text-sm font-semibold text-[var(--color-primary-foreground)] transition hover:brightness-110"
                            >
                                Subscribe
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            {{-- Link columns --}}
            @foreach ($columns as $column)
                <nav
                    aria-label="{{ $column['heading'] ?? 'Footer column' }}"
                    class="lg:col-span-1"
                >
                    <h2
                        class="text-xs font-semibold uppercase tracking-widest text-[var(--color-fg)]"
                    >
                        {{ $column['heading'] ?? '' }}
                    </h2>
                    <ul
                        role="list"
                        class="mt-4 space-y-3"
                    >
                        @foreach ($column['links'] ?? [] as $link)
                            <li>
                                <a
                                    href="{{ $link['url'] ?? '#' }}"
                                    class="text-sm text-[var(--color-fg-muted)] transition hover:text-[var(--color-fg)]"
                                >
                                    {{ $link['label'] ?? '' }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            @endforeach
        </div>

        <div
            class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-[var(--color-border)] pt-6"
        >
            <p class="text-xs text-[var(--color-fg-muted)]">
                {{ $copyright ?? '(c) ' . date('Y') . ' ' . $brand . '. All rights reserved.' }}
            </p>

            @if (! empty($socials))
                <ul
                    role="list"
                    class="flex items-center gap-3"
                >
                    @foreach ($socials as $social)
                        <li>
                            <a
                                href="{{ $social['url'] ?? '#' }}"
                                aria-label="{{ $social['label'] ?? '' }}"
                                class="flex h-8 w-8 items-center justify-center rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] text-xs font-semibold text-[var(--color-fg-muted)] transition hover:text-[var(--color-fg)]"
                            >
                                {{ substr($social['label'] ?? '', 0, 2) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            {{ $slot ?? '' }}
        </div>
    </div>
</footer>
