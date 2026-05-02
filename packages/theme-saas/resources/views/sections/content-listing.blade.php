<section class="mx-auto max-w-7xl px-6 py-16">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <h2 class="text-3xl font-bold tracking-tight">
                {{ $section->heading }}
            </h2>
            @if ($section->summary)
                <p class="mt-3 max-w-2xl text-slate-600">
                    {{ $section->summary }}
                </p>
            @endif
        </div>
    </div>
    <div class="mt-10 grid gap-4 md:grid-cols-3">
        @foreach ($section->items as $item)
            <a
                href="{{ $item['url'] ?? '#' }}"
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm hover:border-[var(--theme-primary)]"
            >
                <h3 class="font-semibold">{{ $item['title'] }}</h3>
                <p class="mt-2 text-sm text-slate-600">
                    {{ $item['summary'] ?? '' }}
                </p>
            </a>
        @endforeach
    </div>
</section>
