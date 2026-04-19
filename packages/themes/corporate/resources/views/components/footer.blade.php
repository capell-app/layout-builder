@props([
    'brand' => 'Capell',
    'tagline' => 'A modern CMS for serious sites.',
    'columns' => [],
    'copyright' => null,
    'layout' => 'expanded',
])

<footer
    role="contentinfo"
    class="border-t border-[var(--color-border)] bg-[var(--color-bg-muted)] py-12"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if ($layout === 'minimal')
            <div class="flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm text-[var(--color-fg-muted)]">
                    {{ $brand }} — {{ $tagline }}
                </p>
                <p class="text-xs text-[var(--color-fg-muted)]">
                    {{ $copyright ?? '(c) ' . date('Y') . ' ' . $brand . '. All rights reserved.' }}
                </p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-10 md:grid-cols-4">
                <div class="md:col-span-1">
                    <p
                        class="text-lg font-semibold text-[var(--color-primary)]"
                    >
                        {{ $brand }}
                    </p>
                    <p class="mt-2 text-sm text-[var(--color-fg-muted)]">
                        {{ $tagline }}
                    </p>
                </div>

                @foreach ($columns as $column)
                    <nav
                        aria-label="{{ $column['heading'] ?? 'Footer column' }}"
                    >
                        <h2
                            class="text-sm font-semibold uppercase tracking-widest"
                        >
                            {{ $column['heading'] ?? '' }}
                        </h2>
                        <ul
                            role="list"
                            class="mt-4 space-y-2"
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

                @if ($layout === 'newsletter')
                    <form
                        class="md:col-span-1"
                        aria-label="Newsletter signup"
                    >
                        <label
                            for="footer-newsletter"
                            class="text-sm font-semibold uppercase tracking-widest"
                        >
                            Newsletter
                        </label>
                        <div class="mt-4 flex gap-2">
                            <input
                                id="footer-newsletter"
                                type="email"
                                placeholder="your@email.com"
                                class="block w-full rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-2 text-sm"
                            />
                            <button
                                type="submit"
                                class="shrink-0 rounded-md bg-[var(--color-primary)] px-4 py-2 text-sm font-semibold text-[var(--color-primary-foreground)]"
                            >
                                Subscribe
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <div
                class="mt-10 border-t border-[var(--color-border)] pt-6 text-xs text-[var(--color-fg-muted)]"
            >
                {{ $copyright ?? '(c) ' . date('Y') . ' ' . $brand . '. All rights reserved.' }}
                {{ $slot ?? '' }}
            </div>
        @endif
    </div>
</footer>
