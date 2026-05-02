<section class="mx-auto max-w-7xl px-6 py-20">
    <div class="rounded-[2rem] bg-white p-8 shadow-sm">
        <h2 class="text-4xl font-black tracking-tight">
            {{ $section->heading }}
        </h2>
        @if ($section->summary)
            <p class="mt-3 max-w-2xl text-zinc-600">{{ $section->summary }}</p>
        @endif

        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ($section->items as $item)
                <figure class="rounded-2xl bg-zinc-950 p-6 text-white">
                    <blockquote class="text-lg font-semibold leading-7">
                        {{ $item['quote'] ?? $item['metric'] ?? '' }}
                    </blockquote>
                    <figcaption class="mt-5 text-sm text-[var(--theme-accent)]">
                        {{ $item['name'] ?? $item['logo'] ?? '' }}
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
