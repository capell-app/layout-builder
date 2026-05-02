<section class="px-6 py-20">
    <div
        class="mx-auto max-w-7xl rounded-[2rem] bg-gradient-to-br from-[var(--theme-primary)] to-[var(--theme-accent)] p-10 text-white"
    >
        <h2 class="max-w-3xl text-5xl font-black tracking-tight">
            {{ $section->heading }}
        </h2>
        @if ($section->summary)
            <p class="mt-4 max-w-2xl text-white/80">{{ $section->summary }}</p>
        @endif

        <div class="mt-8 flex flex-wrap gap-3">
            @foreach ($section->actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="rounded-full bg-white px-6 py-3 text-sm font-bold text-zinc-950"
                >
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</section>
