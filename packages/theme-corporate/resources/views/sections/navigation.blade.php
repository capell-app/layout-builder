<nav
    class="sticky top-0 z-40 border-b border-slate-200/80 bg-[#f7f8f6]/90 backdrop-blur"
>
    <div
        class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 sm:px-6"
    >
        <a href="/" class="text-sm font-semibold tracking-wide text-slate-950">
            {{ $section->brandName }}
        </a>
        <div class="hidden items-center gap-7 text-sm text-slate-600 md:flex">
            @foreach ($section->items as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="transition hover:text-slate-950"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
        @if ($section->ctaLabel && $section->ctaUrl)
            <a
                href="{{ $section->ctaUrl }}"
                class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-800 transition hover:border-slate-950 hover:text-slate-950"
            >
                {{ $section->ctaLabel }}
            </a>
        @endif
    </div>
</nav>
