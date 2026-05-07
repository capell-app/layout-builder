<section
    class="border-b border-slate-800 bg-slate-950 text-white dark:border-white/10 dark:bg-black"
>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:py-16">
        <div class="grid gap-6 lg:grid-cols-[0.8fr_1.2fr] lg:gap-8">
            <div>
                <p
                    class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--theme-accent)]"
                >
                    Proof
                </p>
                <h2
                    class="max-w-xl text-2xl font-semibold leading-tight sm:text-3xl lg:text-4xl"
                >
                    {{ $section->heading }}
                </h2>
                @if ($section->summary)
                    <p
                        class="mt-3 max-w-md text-sm leading-6 text-slate-300 sm:leading-7"
                    >
                        {{ $section->summary }}
                    </p>
                @endif
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:gap-4">
                @foreach ($section->items as $item)
                    <figure
                        class="rounded-[0.35rem] border border-white/10 bg-white/[0.03] p-5 sm:p-6"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                alt=""
                                class="mb-4 aspect-[5/3] w-full rounded-[0.25rem] object-cover opacity-90 sm:mb-5 sm:aspect-[16/9]"
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
