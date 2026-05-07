<section class="border-b border-slate-800 bg-slate-950 text-white">
    <div class="mx-auto max-w-7xl px-5 py-16 sm:px-6 lg:py-20">
        <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
                <p
                    class="mb-4 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--theme-accent)]"
                >
                    Proof
                </p>
                <h2
                    class="max-w-xl text-3xl font-semibold leading-tight sm:text-4xl"
                >
                    {{ $section->heading }}
                </h2>
                @if ($section->summary)
                    <p class="mt-4 max-w-md text-sm leading-7 text-slate-300">
                        {{ $section->summary }}
                    </p>
                @endif
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($section->items as $item)
                    <figure
                        class="rounded-[0.35rem] border border-white/10 bg-white/[0.03] p-6"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                alt=""
                                class="mb-5 aspect-[16/9] w-full rounded-[0.25rem] object-cover opacity-90"
                            />
                        @endif

                        <blockquote class="text-sm leading-7 text-slate-200">
                            {{ $item['quote'] ?? $item['metric'] ?? $item['summary'] ?? '' }}
                        </blockquote>
                        <figcaption
                            class="mt-5 flex flex-wrap items-center gap-2 text-xs text-slate-400"
                        >
                            <span class="font-semibold text-white">
                                {{ $item['title'] ?? $item['name'] ?? $item['logo'] ?? '' }}
                            </span>
                            @if (! empty($item['type']))
                                <span class="text-slate-600">/</span>
                                <span>{{ $item['type'] }}</span>
                            @endif

                            @if (! empty($item['publishedDate']))
                                <span class="text-slate-600">/</span>
                                <time
                                    datetime="{{ $item['publishedAt'] ?? '' }}"
                                >
                                    {{ $item['publishedDate'] }}
                                </time>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </div>
</section>
