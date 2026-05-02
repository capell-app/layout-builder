<section class="overflow-hidden bg-zinc-950 text-white">
    <div
        class="mx-auto grid max-w-7xl gap-10 px-6 py-24 lg:grid-cols-[0.95fr_1.05fr]"
    >
        <div>
            @if ($section->eyebrow)
                <p
                    class="mb-6 text-sm font-bold uppercase tracking-[0.25em] text-[var(--theme-accent)]"
                >
                    {{ $section->eyebrow }}
                </p>
            @endif

            <h1 class="text-6xl font-black tracking-tight md:text-7xl">
                {{ $section->heading }}
            </h1>
            @if ($section->summary)
                <p class="mt-6 max-w-xl text-lg leading-8 text-white/70">
                    {{ $section->summary }}
                </p>
            @endif

            <div class="mt-8 flex flex-wrap gap-3">
                @foreach ($section->actions as $action)
                    <a
                        href="{{ $action['url'] }}"
                        class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-white/25 text-white' : 'bg-[var(--theme-primary)] text-white' }} rounded-full px-6 py-3 text-sm font-bold"
                    >
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
        @if ($section->mediaUrl)
            <div
                class="rotate-2 rounded-[2rem] bg-gradient-to-br from-[var(--theme-primary)] to-[var(--theme-accent)] p-3 shadow-2xl"
            >
                <img
                    src="{{ $section->mediaUrl }}"
                    alt="{{ $section->mediaAlt ?? '' }}"
                    class="aspect-[4/3] w-full rounded-[1.5rem] border border-white/25 object-cover"
                />
            </div>
        @endif
    </div>
</section>
