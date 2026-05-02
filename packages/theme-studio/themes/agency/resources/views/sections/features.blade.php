<section class="mx-auto max-w-7xl px-6 py-20">
    <h2 class="max-w-3xl text-5xl font-black tracking-tight">
        {{ $section->heading }}
    </h2>
    @if ($section->summary)
        <p class="mt-4 max-w-2xl text-zinc-600">{{ $section->summary }}</p>
    @endif

    <div class="mt-10 grid gap-5 md:grid-cols-3">
        @foreach ($section->features as $feature)
            <article
                class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:-translate-y-1"
            >
                <p
                    class="text-sm font-bold uppercase tracking-widest text-[var(--theme-primary)]"
                >
                    {{ $feature['icon'] ?? 'Studio' }}
                </p>
                <h3 class="mt-5 text-xl font-bold">{{ $feature['title'] }}</h3>
                <p class="mt-3 text-sm leading-6 text-zinc-600">
                    {{ $feature['description'] }}
                </p>
            </article>
        @endforeach
    </div>
</section>
