@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $milestones = is_array($meta['milestones'] ?? null) ? $meta['milestones'] : [];
@endphp

<section
    {{ $attributes->merge(['class' => 'content-block content-block-timeline']) }}
>
    @if ($title || $summary)
        <header class="mb-8">
            @if ($title)
                <h2 class="text-3xl font-bold">{{ $title }}</h2>
            @endif

            @if ($summary)
                <div class="mt-3 text-lg opacity-80">{!! $summary !!}</div>
            @endif
        </header>
    @endif

    <ol class="relative space-y-6 border-l border-slate-200 pl-6">
        @foreach ($milestones as $milestone)
            <li>
                <span
                    class="absolute -left-2 mt-2 h-4 w-4 rounded-full bg-slate-950"
                ></span>
                <p
                    class="text-sm font-semibold uppercase tracking-wide text-slate-500"
                >
                    {{ $milestone['date'] ?? '' }}
                </p>
                <h3 class="mt-1 text-lg font-semibold">
                    {{ $milestone['heading'] ?? '' }}
                </h3>

                @if (filled($milestone['description'] ?? null))
                    <p class="mt-2 text-slate-600">
                        {{ $milestone['description'] }}
                    </p>
                @endif
            </li>
        @endforeach
    </ol>
</section>
