<section
    class="border-b border-slate-200/80 bg-white dark:border-white/10 dark:bg-slate-900"
>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:py-16">
        <div class="grid gap-6 lg:grid-cols-[0.72fr_1.28fr] lg:gap-8">
            <div>
                <p
                    class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--theme-primary)] dark:text-[var(--theme-accent)]"
                >
                    Features
                </p>
                <h2
                    class="max-w-lg text-2xl font-semibold leading-tight text-slate-950 sm:text-3xl lg:text-4xl dark:text-white"
                >
                    {{ $section->heading }}
                </h2>
                @if ($section->summary)
                    <p
                        class="mt-3 max-w-md text-sm leading-6 text-slate-600 sm:leading-7 dark:text-slate-300"
                    >
                        {{ $section->summary }}
                    </p>
                @endif
            </div>

            <div class="grid gap-3 md:grid-cols-2 lg:gap-4">
                @foreach ($section->features as $feature)
                    <article
                        class="{{ $loop->first ? 'md:col-span-2 md:grid md:grid-cols-[0.9fr_1.1fr] md:items-stretch' : '' }} overflow-hidden rounded-[0.35rem] border border-slate-200 bg-[#f7f8f6] dark:border-white/10 dark:bg-white/[0.03]"
                    >
                        @if (! empty($feature['image']))
                            <img
                                src="{{ $feature['image'] }}"
                                alt=""
                                class="{{ $loop->first ? 'aspect-[5/3] md:h-full' : 'aspect-[5/3] sm:aspect-[4/3]' }} w-full object-cover"
                            />
                        @endif

                        <div class="p-4 sm:p-5 lg:p-6">
                            <div
                                class="mb-3 flex items-center gap-3 text-xs text-slate-500 sm:mb-5 dark:text-slate-400"
                            >
                                <span
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-300 text-slate-700 dark:border-white/15 dark:text-slate-200"
                                >
                                    {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </span>
                                @if (! empty($feature['type']))
                                    <span>{{ $feature['type'] }}</span>
                                @endif
                            </div>
                            <h3
                                class="text-base font-semibold text-slate-950 sm:text-lg dark:text-white"
                            >
                                {{ $feature['title'] }}
                            </h3>
                            <p
                                class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600 dark:text-slate-300"
                            >
                                {{ $feature['description'] ?? $feature['summary'] ?? '' }}
                            </p>
                            @if (! empty($feature['publishedDate']) || ! empty($feature['author']))
                                <p
                                    class="mt-3 text-xs text-slate-500 sm:mt-5 dark:text-slate-400"
                                >
                                    @if (! empty($feature['publishedAt']) && ! empty($feature['publishedDate']))
                                        <time
                                            datetime="{{ $feature['publishedAt'] }}"
                                        >
                                            {{ $feature['publishedDate'] }}
                                        </time>
                                    @endif

                                    @if (! empty($feature['author']))
                                        <span>
                                            {{ ! empty($feature['publishedDate']) ? ' / ' : '' }}{{ $feature['author'] }}
                                        </span>
                                    @endif
                                </p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
