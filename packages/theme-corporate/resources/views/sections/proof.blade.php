<section class="bg-slate-950 text-white">
    <div class="mx-auto max-w-7xl px-6 py-16">
        <h2 class="text-3xl font-semibold tracking-tight">
            {{ $section->heading }}
        </h2>
        @if ($section->summary)
            <p class="mt-3 max-w-2xl text-slate-300">
                {{ $section->summary }}
            </p>
        @endif

        <div class="mt-10 grid gap-4 md:grid-cols-3">
            @foreach ($section->items as $item)
                <figure
                    class="rounded-lg border border-white/10 bg-white/5 p-6"
                >
                    <blockquote class="text-sm leading-6 text-slate-200">
                        {{ $item['quote'] ?? $item['metric'] ?? '' }}
                    </blockquote>
                    <figcaption
                        class="mt-4 text-sm font-semibold text-[var(--theme-accent)]"
                    >
                        {{ $item['name'] ?? $item['logo'] ?? '' }}
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
