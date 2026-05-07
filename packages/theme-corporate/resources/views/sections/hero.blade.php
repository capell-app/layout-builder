<section class="border-b border-slate-200/80 bg-[#f7f8f6]">
    <div
        class="mx-auto grid max-w-7xl items-end gap-10 px-5 py-16 sm:px-6 md:py-24 lg:grid-cols-[0.95fr_1.05fr]"
    >
        <div>
            @if ($section->eyebrow)
                <p
                    class="mb-5 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--theme-primary)]"
                >
                    {{ $section->eyebrow }}
                </p>
            @endif

            <h1
                class="max-w-4xl text-5xl font-semibold leading-[0.95] text-slate-950 sm:text-6xl lg:text-7xl"
            >
                {{ $section->heading }}
            </h1>
            @if ($section->summary)
                <p
                    class="mt-6 max-w-xl text-base leading-8 text-slate-600 sm:text-lg"
                >
                    {{ $section->summary }}
                </p>
            @endif

            <div class="mt-8 flex flex-wrap gap-3">
                @foreach ($section->actions as $action)
                    <a
                        href="{{ $action['url'] }}"
                        class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-slate-300 text-slate-800 hover:border-slate-950' : 'bg-slate-950 text-white hover:bg-[var(--theme-primary)]' }} rounded-full px-5 py-3 text-sm font-semibold transition"
                    >
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
        @if ($section->mediaUrl)
            <figure class="relative">
                <img
                    src="{{ $section->mediaUrl }}"
                    alt="{{ $section->mediaAlt ?? '' }}"
                    class="aspect-[5/4] w-full rounded-[0.35rem] object-cover"
                />
                <figcaption
                    class="mt-3 flex items-center justify-between gap-4 text-xs uppercase tracking-[0.16em] text-slate-500"
                >
                    <span>{{ $section->mediaAlt ?? $section->heading }}</span>
                    <span class="h-px grow bg-slate-300"></span>
                </figcaption>
            </figure>
        @endif
    </div>
</section>
