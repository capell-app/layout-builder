<section class="mx-auto max-w-7xl px-6 py-16">
    <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-3xl font-bold tracking-tight">
            {{ $section->heading }}
        </h2>
        @if ($section->summary)
            <p class="mt-3 text-slate-600">{{ $section->summary }}</p>
        @endif
    </div>
    <div class="mt-10 grid gap-4 md:grid-cols-3">
        @foreach ($section->features as $feature)
            <article
                class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
            >
                <div
                    class="bg-[var(--theme-primary)]/10 mb-4 h-10 w-10 rounded-lg"
                ></div>
                <h3 class="font-semibold">{{ $feature['title'] }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    {{ $feature['description'] }}
                </p>
            </article>
        @endforeach
    </div>
</section>
