<nav class="bg-zinc-950 text-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
        <a href="/" class="text-2xl font-black tracking-tight">
            {{ $section->brandName }}
        </a>
        <div
            class="hidden items-center gap-6 text-sm font-semibold uppercase tracking-wide text-white/70 md:flex"
        >
            @foreach ($section->items as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="hover:text-[var(--theme-accent)]"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
        @if ($section->ctaLabel && $section->ctaUrl)
            <a
                href="{{ $section->ctaUrl }}"
                class="rounded-full bg-[var(--theme-primary)] px-5 py-2 text-sm font-bold text-white"
            >
                {{ $section->ctaLabel }}
            </a>
        @endif
    </div>
</nav>
