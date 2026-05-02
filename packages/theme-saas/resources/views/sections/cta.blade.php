<section class="mx-auto max-w-7xl px-6 py-16">
    <div class="rounded-2xl bg-slate-950 p-10 text-center text-white">
        <h2 class="text-3xl font-bold tracking-tight">
            {{ $section->heading }}
        </h2>
        @if ($section->summary)
            <p class="mx-auto mt-3 max-w-2xl text-white/70">
                {{ $section->summary }}
            </p>
        @endif

        <div class="mt-8 flex justify-center gap-3">
            @foreach ($section->actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="rounded-lg bg-[var(--theme-accent)] px-5 py-3 text-sm font-semibold text-white"
                >
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</section>
