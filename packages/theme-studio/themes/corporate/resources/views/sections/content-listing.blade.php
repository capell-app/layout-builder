<section class="mx-auto max-w-7xl px-6 py-16">
    <h2 class="text-3xl font-semibold tracking-tight">
        {{ $section->heading }}
    </h2>
    @if ($section->summary)
        <p class="mt-3 max-w-2xl text-slate-600">{{ $section->summary }}</p>
    @endif

    <div
        class="mt-10 divide-y divide-slate-200 rounded-lg border border-slate-200"
    >
        @foreach ($section->items as $item)
            <a
                href="{{ $item['url'] ?? '#' }}"
                class="block p-6 hover:bg-slate-50"
            >
                <h3 class="font-semibold">{{ $item['title'] }}</h3>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $item['summary'] ?? '' }}
                </p>
            </a>
        @endforeach
    </div>
</section>
