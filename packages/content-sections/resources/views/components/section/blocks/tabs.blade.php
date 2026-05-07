@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $tabs = is_array($meta['tabs'] ?? null) ? array_values($meta['tabs']) : [];
    $group = 'section-tabs-' . substr(md5(json_encode($tabs)), 0, 8);
@endphp

<section
    {{ $attributes->merge(['class' => 'section section-tabs']) }}
>
    @if ($title || $summary)
        <header class="mb-6">
            @if ($title)
                <h2 class="text-3xl font-bold">{{ $title }}</h2>
            @endif

            @if ($summary)
                <div class="mt-3 text-lg opacity-80">{!! $summary !!}</div>
            @endif
        </header>
    @endif

    <div class="rounded-lg border border-slate-200 bg-white p-2">
        <div class="flex flex-wrap gap-2" role="tablist">
            @foreach ($tabs as $tab)
                <a
                    class="rounded px-4 py-2 font-semibold text-slate-600 first:bg-slate-950 first:text-white"
                    href="#{{ $group }}-{{ $loop->index }}"
                >
                    {{ $tab['label'] ?? '' }}
                </a>
            @endforeach
        </div>

        <div class="mt-4 space-y-3">
            @foreach ($tabs as $tab)
                <article
                    id="{{ $group }}-{{ $loop->index }}"
                    class="rounded bg-slate-50 p-5"
                >
                    <h3 class="mb-2 font-semibold">
                        {{ $tab['label'] ?? '' }}
                    </h3>
                    <div class="prose max-w-none">
                        {!! $tab['content'] ?? '' !!}
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
