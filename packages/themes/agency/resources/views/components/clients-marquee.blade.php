@props([
    'title' => null,
    'speed' => 'medium',
    'clients' => [],
])

<section aria-labelledby="clients-title"
         class="border-y border-[var(--color-border)] bg-[var(--color-bg-muted)] py-10">
    <div class="mx-auto max-w-[1440px] px-4 sm:px-6 lg:px-10">
        @if ($title)
            <h2 id="clients-title"
                class="mb-6 text-center text-xs font-semibold uppercase tracking-[0.3em] text-[var(--color-fg-muted)]">
                {{ $title }}
            </h2>
        @else
            <h2 id="clients-title" class="sr-only">Clients</h2>
        @endif

        <div aria-hidden="true" class="relative overflow-hidden">
            <div class="agency-marquee-track flex gap-14 whitespace-nowrap" data-speed="{{ $speed }}">
                {{-- Duplicate the list once for seamless loop --}}
                @for ($pass = 0; $pass < 2; $pass++)
                    @foreach ($clients as $client)
                        <div class="flex shrink-0 items-center gap-3">
                            @if (! empty($client['logo']))
                                <img src="{{ $client['logo'] }}"
                                     alt=""
                                     loading="lazy"
                                     class="h-8 w-auto opacity-70" />
                            @else
                                <span class="agency-display text-3xl font-semibold text-[var(--color-fg)]/70">
                                    {{ $client['name'] ?? '' }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                @endfor
            </div>
        </div>

        {{-- Visually hidden list for screen readers --}}
        <ul role="list" class="sr-only">
            @foreach ($clients as $client)
                <li>{{ $client['name'] ?? '' }}</li>
            @endforeach
        </ul>
    </div>
</section>
