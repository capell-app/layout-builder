<section class="bg-slate-50">
    <div class="mx-auto max-w-7xl px-6 py-16">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight">
                {{ $section->heading }}
            </h2>
            @if ($section->summary)
                <p class="mt-3 text-slate-600">{{ $section->summary }}</p>
            @endif
        </div>
        <div class="mt-10 grid gap-4 md:grid-cols-3">
            @foreach ($section->items as $item)
                <figure
                    class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <blockquote class="text-sm leading-6 text-slate-700">
                        {{ $item['quote'] ?? $item['metric'] ?? '' }}
                    </blockquote>
                    <figcaption
                        class="mt-4 text-sm font-semibold text-[var(--theme-primary)]"
                    >
                        {{ $item['name'] ?? $item['logo'] ?? '' }}
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
