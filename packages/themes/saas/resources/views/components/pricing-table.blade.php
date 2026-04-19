@props([
    'title' => 'Simple, transparent pricing',
    'subtitle' => 'Pick the plan that fits. Upgrade or downgrade anytime.',
    'cycleDefault' => 'monthly',
    'annualDiscountLabel' => 'Save 20%',
    'tiers' => [],
])

<section
    id="pricing"
    aria-labelledby="pricing-title"
    class="pricing-root bg-[var(--color-bg)] py-[var(--section-y,5rem)]"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mx-auto mb-10 max-w-2xl text-center">
            <h2
                id="pricing-title"
                class="text-3xl font-bold tracking-tight sm:text-4xl"
            >
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-4 text-[var(--color-fg-muted)]">
                    {{ $subtitle }}
                </p>
            @endif

            <div
                class="pricing-toggle mt-8 inline-flex items-center gap-3 rounded-full border border-[var(--color-border)] bg-[var(--color-bg-muted)] px-4 py-2 text-sm"
            >
                <label
                    for="pricing-cycle"
                    class="cursor-pointer font-medium text-[var(--color-fg)]"
                >
                    Monthly
                </label>
                <input
                    id="pricing-cycle"
                    type="checkbox"
                    class="pricing-cycle"
                    aria-label="Toggle annual billing"
                    @checked($cycleDefault === 'annual')
                />
                <span
                    class="toggle-track"
                    aria-hidden="true"
                >
                    <span class="toggle-thumb"></span>
                </span>
                <label
                    for="pricing-cycle"
                    class="cursor-pointer font-medium text-[var(--color-fg)]"
                >
                    Annual
                    <span
                        class="ml-1 rounded-full bg-[var(--color-accent-soft)] px-2 py-0.5 text-xs font-semibold text-[var(--color-accent)]"
                    >
                        {{ $annualDiscountLabel }}
                    </span>
                </label>
            </div>
        </header>

        <ul
            role="list"
            class="grid grid-cols-1 gap-6 lg:grid-cols-3"
        >
            @forelse ($tiers as $tier)
                @php
                    $highlight = $tier['highlight'] ?? false;
                @endphp

                <li
                    class="{{ $highlight ? 'border-[var(--color-primary)] bg-[var(--color-bg)] shadow-[var(--shadow-elevated)]' : 'border-[var(--color-border)] bg-[var(--color-bg)] shadow-[var(--shadow-card)]' }} relative flex flex-col rounded-2xl border p-8"
                >
                    @if (! empty($tier['badge']))
                        <span
                            class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full bg-[var(--color-primary)] px-3 py-1 text-xs font-semibold text-[var(--color-primary-foreground)]"
                        >
                            {{ $tier['badge'] }}
                        </span>
                    @endif

                    <h3 class="text-lg font-bold text-[var(--color-fg)]">
                        {{ $tier['name'] ?? '' }}
                    </h3>
                    @if (! empty($tier['description']))
                        <p class="mt-2 text-sm text-[var(--color-fg-muted)]">
                            {{ $tier['description'] }}
                        </p>
                    @endif

                    <div class="mt-6 flex items-baseline gap-2">
                        @if (isset($tier['price_monthly']) && $tier['price_monthly'] !== null)
                            <span
                                class="price-monthly flex items-baseline gap-1"
                            >
                                <span
                                    class="text-4xl font-bold tracking-tight text-[var(--color-fg)]"
                                >
                                    ${{ $tier['price_monthly'] }}
                                </span>
                                <span
                                    class="text-sm text-[var(--color-fg-muted)]"
                                >
                                    /mo
                                </span>
                            </span>
                        @endif

                        @if (isset($tier['price_annual']) && $tier['price_annual'] !== null)
                            <span
                                class="price-annual flex items-baseline gap-1"
                            >
                                <span
                                    class="text-4xl font-bold tracking-tight text-[var(--color-fg)]"
                                >
                                    ${{ $tier['price_annual'] }}
                                </span>
                                <span
                                    class="text-sm text-[var(--color-fg-muted)]"
                                >
                                    /mo, billed yearly
                                </span>
                            </span>
                        @endif

                        @if (empty($tier['price_monthly']) && empty($tier['price_annual']))
                            <span
                                class="text-4xl font-bold tracking-tight text-[var(--color-fg)]"
                            >
                                {{ $tier['custom_price_label'] ?? 'Custom' }}
                            </span>
                        @endif
                    </div>

                    <ul
                        role="list"
                        class="mt-6 space-y-3 text-sm text-[var(--color-fg-muted)]"
                    >
                        @foreach (($tier['features'] ?? []) as $feature)
                            <li class="flex items-start gap-2">
                                <span
                                    class="saas-check mt-0.5 shrink-0"
                                    aria-hidden="true"
                                >
                                    &#10003;
                                </span>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-8">
                        <a
                            href="{{ $tier['cta_url'] ?? '#' }}"
                            class="{{ $highlight ? 'bg-[var(--color-primary)] text-[var(--color-primary-foreground)] hover:brightness-110' : 'border border-[var(--color-border)] bg-[var(--color-bg)] text-[var(--color-fg)] hover:bg-[var(--color-bg-muted)]' }} inline-flex w-full items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold transition"
                        >
                            {{ $tier['cta_label'] ?? 'Get started' }}
                        </a>
                    </div>
                </li>
            @empty
                <li
                    class="col-span-full text-center text-[var(--color-fg-muted)]"
                >
                    No pricing tiers configured.
                </li>
            @endforelse
        </ul>
    </div>
</section>
