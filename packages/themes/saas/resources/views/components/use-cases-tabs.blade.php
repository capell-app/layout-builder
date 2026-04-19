@props([
    'title' => 'Built for every team',
    'subtitle' => 'See how teams like yours ship with Capell.',
    'useCases' => [],
])

@php $name = 'usecase-'.substr(md5(json_encode($useCases)), 0, 6); @endphp

<section id="use-cases"
         aria-labelledby="use-cases-title"
         class="bg-[var(--color-bg-muted)] py-[var(--section-y,5rem)]">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <header class="mx-auto mb-10 max-w-2xl text-center">
            <h2 id="use-cases-title" class="text-3xl font-bold tracking-tight sm:text-4xl">
                {{ $title }}
            </h2>
            @if ($subtitle)
                <p class="mt-4 text-[var(--color-fg-muted)]">{{ $subtitle }}</p>
            @endif
        </header>

        @if (empty($useCases))
            <p class="text-center text-[var(--color-fg-muted)]">No use cases configured.</p>
        @else
            <div class="overflow-hidden rounded-2xl border border-[var(--color-border)] bg-[var(--color-bg)] shadow-[var(--shadow-card)]">
                {{-- Tab buttons: CSS-only radio-based tabs --}}
                <div role="tablist" aria-label="Use cases"
                     class="flex flex-wrap gap-2 border-b border-[var(--color-border)] bg-[var(--color-bg-muted)] p-2">
                    @foreach ($useCases as $i => $case)
                        <input type="radio"
                               id="{{ $name }}-tab-{{ $i }}"
                               name="{{ $name }}"
                               class="peer sr-only"
                               @checked($i === 0)
                               data-tab-index="{{ $i }}" />
                        <label for="{{ $name }}-tab-{{ $i }}"
                               role="tab"
                               tabindex="0"
                               aria-controls="{{ $name }}-panel-{{ $i }}"
                               class="cursor-pointer rounded-lg px-4 py-2 text-sm font-semibold text-[var(--color-fg-muted)] transition hover:text-[var(--color-fg)] peer-checked:bg-[var(--color-bg)] peer-checked:text-[var(--color-primary)] peer-checked:shadow-sm">
                            {{ $case['label'] ?? $case['id'] ?? 'Tab' }}
                        </label>
                    @endforeach
                </div>

                {{-- Panels --}}
                <div class="p-6 sm:p-10">
                    @foreach ($useCases as $i => $case)
                        <div id="{{ $name }}-panel-{{ $i }}"
                             role="tabpanel"
                             aria-labelledby="{{ $name }}-tab-{{ $i }}"
                             @class([
                                 'grid gap-8 md:grid-cols-2 md:items-center',
                                 'hidden' => $i !== 0,
                             ])
                             data-use-case-panel="{{ $i }}">
                            <div>
                                <h3 class="text-2xl font-bold tracking-tight text-[var(--color-fg)]">
                                    {{ $case['heading'] ?? '' }}
                                </h3>
                                @if (! empty($case['description']))
                                    <p class="mt-3 text-[var(--color-fg-muted)]">{{ $case['description'] }}</p>
                                @endif

                                <ul role="list" class="mt-6 space-y-3">
                                    @foreach (($case['benefits'] ?? []) as $benefit)
                                        <li class="flex items-start gap-2 text-sm">
                                            <span class="saas-check mt-0.5 shrink-0" aria-hidden="true">&#10003;</span>
                                            <span>{{ $benefit }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="aspect-[4/3] rounded-xl border border-[var(--color-border)] bg-[var(--color-bg-muted)] shadow-inner"
                                 style="background-image: @if(! empty($case['image_url'])) url('{{ $case['image_url'] }}'); background-size: cover; background-position: center; @else linear-gradient(135deg, var(--color-primary-soft), var(--color-accent-soft)); @endif"
                                 role="img"
                                 aria-label="{{ $case['heading'] ?? '' }} illustration">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Tiny inline script wires tab visibility without Alpine/framework dependency --}}
            <script>
                (function() {
                    var tabs = document.querySelectorAll('input[name="{{ $name }}"]');
                    tabs.forEach(function(tab) {
                        tab.addEventListener('change', function() {
                            var idx = this.getAttribute('data-tab-index');
                            document.querySelectorAll('[data-use-case-panel]').forEach(function(p) {
                                p.classList.toggle('hidden', p.getAttribute('data-use-case-panel') !== idx);
                            });
                        });
                    });
                })();
            </script>
        @endif
    </div>
</section>
