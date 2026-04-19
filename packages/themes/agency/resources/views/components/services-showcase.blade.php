@props([
    'title' => 'Services',
    'subtitle' => null,
    'services' => [],
])

<section
    id="services"
    aria-labelledby="services-title"
    class="bg-[var(--color-bg)] py-[var(--section-y,5rem)]"
>
    <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-10">
        <header class="mx-auto mb-14 max-w-2xl text-center">
            <h2
                id="services-title"
                class="agency-display text-4xl sm:text-5xl md:text-6xl"
            >
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-4 text-lg text-[var(--color-fg-muted)]">
                    {{ $subtitle }}
                </p>
            @endif
        </header>

        <ul
            role="list"
            class="space-y-4"
        >
            @forelse ($services as $service)
                <li>
                    <details
                        class="group rounded-[var(--radius-lg)] border border-[var(--color-border)] bg-[var(--color-bg)] transition hover:border-[var(--color-primary)]"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-4 p-6 sm:p-8"
                        >
                            <div class="flex items-center gap-5">
                                <span
                                    aria-hidden="true"
                                    class="bg-[var(--color-primary)]/10 inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg text-[var(--color-primary)]"
                                >
                                    <span class="text-xl">&#x2726;</span>
                                </span>
                                <div>
                                    <h3
                                        class="text-xl font-semibold sm:text-2xl"
                                    >
                                        {{ $service['title'] ?? '' }}
                                    </h3>
                                    @if (! empty($service['summary']))
                                        <p
                                            class="text-sm text-[var(--color-fg-muted)] sm:text-base"
                                        >
                                            {{ $service['summary'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            <span
                                aria-hidden="true"
                                class="text-2xl text-[var(--color-fg-muted)] transition group-open:rotate-45 group-open:text-[var(--color-primary)]"
                            >
                                +
                            </span>
                        </summary>
                        @if (! empty($service['detail']))
                            <div
                                class="border-t border-[var(--color-border)] p-6 text-[var(--color-fg-muted)] sm:p-8"
                            >
                                {{ $service['detail'] }}
                            </div>
                        @endif
                    </details>
                </li>
            @empty
                <li class="text-center text-[var(--color-fg-muted)]">
                    {{ $slot ?? 'No services configured.' }}
                </li>
            @endforelse
        </ul>
    </div>
</section>
