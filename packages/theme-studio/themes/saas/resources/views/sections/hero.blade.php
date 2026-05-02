<section class="relative overflow-hidden">
    <div
        class="from-[var(--theme-primary)]/15 to-[var(--theme-accent)]/15 absolute inset-x-0 top-0 h-80 bg-gradient-to-br"
    ></div>
    <div
        class="relative mx-auto grid max-w-7xl items-center gap-10 px-6 py-20 lg:grid-cols-[1fr_1fr]"
    >
        <div>
            @if ($section->eyebrow)
                <p
                    class="bg-[var(--theme-primary)]/10 mb-4 inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wider text-[var(--theme-primary)]"
                >
                    {{ $section->eyebrow }}
                </p>
            @endif

            <h1 class="text-5xl font-bold tracking-tight md:text-6xl">
                {{ $section->heading }}
            </h1>
            @if ($section->summary)
                <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-600">
                    {{ $section->summary }}
                </p>
            @endif

            <div class="mt-8 flex flex-wrap gap-3">
                @foreach ($section->actions as $action)
                    <a
                        href="{{ $action['url'] }}"
                        class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-slate-300 text-slate-800' : 'bg-[var(--theme-primary)] text-white shadow-lg' }} rounded-lg px-5 py-3 text-sm font-semibold"
                    >
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
        @if ($section->mediaUrl)
            <div
                class="rounded-2xl border border-slate-200 bg-white p-3 shadow-2xl"
            >
                <img
                    src="{{ $section->mediaUrl }}"
                    alt="{{ $section->mediaAlt ?? '' }}"
                    class="aspect-[4/3] w-full rounded-xl object-cover"
                />
            </div>
        @endif
    </div>
</section>
