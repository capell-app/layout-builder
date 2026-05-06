@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $questions = is_array($meta['questions'] ?? null) ? $meta['questions'] : [];
    $firstOpen = (bool) ($meta['first_open'] ?? false);
@endphp

<section
    {{ $attributes->merge(['class' => 'content-block content-block-faq']) }}
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

    <div class="space-y-3">
        @foreach ($questions as $question)
            <details
                class="rounded-lg border border-slate-200 bg-white p-5"
                @if ($loop->first && $firstOpen) open @endif
            >
                <summary class="cursor-pointer font-semibold">
                    {{ $question['question'] ?? '' }}
                </summary>

                @if (filled($question['answer'] ?? null))
                    <div class="prose mt-3 max-w-none text-slate-700">
                        {!! $question['answer'] !!}
                    </div>
                @endif
            </details>
        @endforeach
    </div>
</section>
