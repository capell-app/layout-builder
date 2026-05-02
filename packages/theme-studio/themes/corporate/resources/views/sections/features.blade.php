<section class="mx-auto max-w-7xl px-6 py-16">
    <div class="max-w-2xl">
        <h2 class="text-3xl font-semibold tracking-tight">
            {{ $section->heading }}
        </h2>
        @if ($section->summary)
            <p class="mt-3 text-slate-600">{{ $section->summary }}</p>
        @endif
    </div>
    <div class="mt-10 grid gap-4 md:grid-cols-3">
        @foreach ($section->features as $feature)
            <article class="rounded-lg border border-slate-200 p-6">
                <div
                    class="bg-[var(--theme-primary)]/10 mb-4 h-10 w-10 rounded-md"
                ></div>
                <h3 class="font-semibold">{{ $feature['title'] }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    {{ $feature['description'] }}
                </p>
            </article>
        @endforeach
    </div>
</section>
