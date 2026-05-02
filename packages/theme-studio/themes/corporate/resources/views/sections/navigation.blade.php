<nav class="border-b border-slate-200 bg-white/95">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
        <a href="/" class="font-semibold text-[var(--theme-primary)]">
            {{ $section->brandName }}
        </a>
        <div
            class="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex"
        >
            @foreach ($section->items as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="hover:text-[var(--theme-primary)]"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
        @if ($section->ctaLabel && $section->ctaUrl)
            <a
                href="{{ $section->ctaUrl }}"
                class="rounded-md bg-[var(--theme-primary)] px-4 py-2 text-sm font-semibold text-white"
            >
                {{ $section->ctaLabel }}
            </a>
        @endif
    </div>
</nav>
