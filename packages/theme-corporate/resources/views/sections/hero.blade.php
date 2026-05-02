<section class="bg-slate-50">
    <div
        class="mx-auto grid max-w-7xl items-center gap-10 px-6 py-20 lg:grid-cols-[1.1fr_0.9fr]"
    >
        <div>
            @if ($section->eyebrow)
                <p
                    class="mb-4 text-sm font-semibold uppercase tracking-widest text-[var(--theme-accent)]"
                >
                    {{ $section->eyebrow }}
                </p>
            @endif

            <h1
                class="max-w-3xl text-5xl font-semibold tracking-tight text-slate-950"
            >
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
                        class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-slate-300 text-slate-800' : 'bg-[var(--theme-primary)] text-white' }} rounded-md px-5 py-3 text-sm font-semibold"
                    >
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
        @if ($section->mediaUrl)
            <div
                class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
            >
                <img
                    src="{{ $section->mediaUrl }}"
                    alt="{{ $section->mediaAlt ?? '' }}"
                    class="aspect-[4/3] w-full rounded-md object-cover"
                />
            </div>
        @endif
    </div>
</section>
