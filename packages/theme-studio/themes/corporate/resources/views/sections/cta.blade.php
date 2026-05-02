<section class="mx-auto max-w-7xl px-6 py-16">
    <div class="rounded-lg bg-[var(--theme-primary)] p-10 text-white">
        <h2 class="text-3xl font-semibold tracking-tight">
            {{ $section->heading }}
        </h2>
        @if ($section->summary)
            <p class="mt-3 max-w-2xl text-white/80">{{ $section->summary }}</p>
        @endif

        <div class="mt-6 flex flex-wrap gap-3">
            @foreach ($section->actions as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="rounded-md bg-white px-5 py-3 text-sm font-semibold text-[var(--theme-primary)]"
                >
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</section>
