@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $plans = is_array($meta['plans'] ?? null) ? $meta['plans'] : [];
@endphp

<section
    {{ $attributes->merge(['class' => 'content-block content-block-pricing']) }}
>
    @if ($title || $summary)
        <header class="mb-8 text-center">
            @if ($title)
                <h2 class="text-3xl font-bold">{{ $title }}</h2>
            @endif

            @if ($summary)
                <div class="mx-auto mt-3 max-w-3xl text-lg opacity-80">
                    {!! $summary !!}
                </div>
            @endif
        </header>
    @endif

    <div class="grid gap-5 md:grid-cols-3">
        @foreach ($plans as $plan)
            <article
                @class(['rounded-lg border bg-white p-6', 'border-slate-950 shadow-lg' => (bool) ($plan['highlighted'] ?? false), 'border-slate-200' => ! (bool) ($plan['highlighted'] ?? false)])
            >
                <h3 class="text-xl font-semibold">
                    {{ $plan['name'] ?? '' }}
                </h3>
                <p class="mt-4 text-4xl font-bold">
                    {{ $plan['price'] ?? '' }}
                    @if (filled($plan['period'] ?? null))
                        <span class="text-base font-medium text-slate-500">
                            /{{ $plan['period'] }}
                        </span>
                    @endif
                </p>

                @if (filled($plan['description'] ?? null))
                    <p class="mt-3 text-slate-600">
                        {{ $plan['description'] }}
                    </p>
                @endif

                @if (filled($plan['features'] ?? null))
                    <ul class="mt-5 space-y-2 text-sm">
                        @foreach (preg_split('/\r\n|\r|\n/', (string) $plan['features']) as $feature)
                            <li class="flex gap-2">
                                <span aria-hidden="true">✓</span>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (filled($plan['action_label'] ?? null) && filled($plan['action_url'] ?? null))
                    <a
                        href="{{ $plan['action_url'] }}"
                        class="mt-6 inline-flex w-full justify-center rounded bg-slate-950 px-4 py-3 font-semibold text-white"
                    >
                        {{ $plan['action_label'] }}
                    </a>
                @endif
            </article>
        @endforeach
    </div>
</section>
