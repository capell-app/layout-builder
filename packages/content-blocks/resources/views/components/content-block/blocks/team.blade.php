@props(['asset', 'meta' => [], 'summary' => null, 'title' => null])

@php
    $members = is_array($meta['members'] ?? null) ? $meta['members'] : [];
    $columns = (string) ($meta['columns'] ?? '3');
@endphp

<section
    {{ $attributes->merge(['class' => 'content-block content-block-team']) }}
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

    <div
        @class(['grid gap-5', 'md:grid-cols-2' => $columns === '2', 'md:grid-cols-3' => $columns === '3', 'md:grid-cols-4' => $columns === '4'])
    >
        @foreach ($members as $member)
            <article class="rounded-lg border border-slate-200 bg-white p-6">
                <div
                    class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-xl font-bold"
                >
                    {{ str($member['name'] ?? '')->substr(0, 1)->upper() }}
                </div>
                <h3 class="text-lg font-semibold">
                    {{ $member['name'] ?? '' }}
                </h3>

                @if (filled($member['role'] ?? null))
                    <p class="text-sm font-medium text-slate-500">
                        {{ $member['role'] }}
                    </p>
                @endif

                @if (filled($member['bio'] ?? null))
                    <p class="mt-3 text-slate-600">{{ $member['bio'] }}</p>
                @endif

                @if (filled($member['url'] ?? null))
                    <a
                        href="{{ $member['url'] }}"
                        class="mt-4 inline-flex font-semibold hover:underline"
                    >
                        {{ __('capell-content-blocks::button.read_more') }}
                    </a>
                @endif
            </article>
        @endforeach
    </div>
</section>
