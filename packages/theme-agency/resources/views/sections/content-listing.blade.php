<section class="mx-auto max-w-7xl px-6 py-20">
    <h2 class="text-5xl font-black tracking-tight">{{ $section->heading }}</h2>
    @if ($section->summary)
        <p class="mt-4 max-w-2xl text-zinc-600">{{ $section->summary }}</p>
    @endif

    <div class="mt-10 grid gap-5 md:grid-cols-3">
        @foreach ($section->items as $item)
            <a
                href="{{ $item['url'] ?? '#' }}"
                class="group rounded-3xl bg-white p-5 shadow-sm"
            >
                <div
                    class="mb-5 aspect-[4/3] rounded-2xl bg-gradient-to-br from-[var(--theme-primary)] to-[var(--theme-accent)]"
                ></div>
                <h3
                    class="text-xl font-bold group-hover:text-[var(--theme-primary)]"
                >
                    {{ $item['title'] }}
                </h3>
                <p class="mt-2 text-sm text-zinc-600">
                    {{ $item['summary'] ?? '' }}
                </p>
            </a>
        @endforeach
    </div>
</section>
