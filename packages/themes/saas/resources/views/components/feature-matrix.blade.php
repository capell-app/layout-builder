@props([
    'title' => 'Everything you need in one platform',
    'subtitle' => 'Compare features across our plans at a glance.',
    'tiers' => [
        ['name' => 'Starter', 'highlight' => false],
        ['name' => 'Growth', 'highlight' => true],
        ['name' => 'Enterprise', 'highlight' => false],
    ],
    'features' => [],
])

<section id="features"
         aria-labelledby="feature-matrix-title"
         class="bg-[var(--color-bg-muted)] py-[var(--section-y,5rem)]">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <header class="mx-auto mb-12 max-w-2xl text-center">
            <h2 id="feature-matrix-title"
                class="text-3xl font-bold tracking-tight sm:text-4xl">
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-4 text-[var(--color-fg-muted)]">{{ $subtitle }}</p>
            @endif
        </header>

        <div class="overflow-x-auto rounded-xl border border-[var(--color-border)] bg-[var(--color-bg)] shadow-[var(--shadow-card)]">
            <table class="w-full min-w-[640px] border-collapse text-left text-sm">
                <caption class="sr-only">Feature availability across plan tiers</caption>
                <thead>
                    <tr class="border-b border-[var(--color-border)]">
                        <th scope="col" class="px-6 py-4 font-semibold">Feature</th>
                        @foreach ($tiers as $tier)
                            <th scope="col"
                                class="px-6 py-4 text-center font-semibold {{ ($tier['highlight'] ?? false) ? 'bg-[var(--color-primary-soft)] text-[var(--color-primary)]' : '' }}">
                                {{ $tier['name'] ?? '' }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($features as $feature)
                        <tr class="border-b border-[var(--color-border-subtle)] last:border-b-0">
                            <th scope="row" class="px-6 py-4 text-left font-medium text-[var(--color-fg)]">
                                {{ $feature['label'] ?? '' }}
                            </th>
                            @foreach (($feature['tiers'] ?? []) as $i => $included)
                                <td class="px-6 py-4 text-center {{ ($tiers[$i]['highlight'] ?? false) ? 'bg-[var(--color-primary-soft)]/40' : '' }}">
                                    @if ($included === true)
                                        <span class="saas-check" aria-label="Included">&#10003;</span>
                                    @elseif (is_string($included))
                                        <span class="text-[var(--color-fg-muted)]">{{ $included }}</span>
                                    @else
                                        <span class="saas-cross" aria-label="Not included">&mdash;</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($tiers) + 1 }}"
                                class="px-6 py-8 text-center text-[var(--color-fg-muted)]">
                                No features configured.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
