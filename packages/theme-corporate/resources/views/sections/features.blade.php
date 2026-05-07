<section class="border-b border-slate-200/80 bg-white">
    <div class="mx-auto max-w-7xl px-5 py-16 sm:px-6 lg:py-20">
        <div class="grid gap-8 lg:grid-cols-[0.72fr_1.28fr]">
            <div>
                <p
                    class="mb-4 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--theme-primary)]"
                >
                    Features
                </p>
                <h2
                    class="max-w-lg text-3xl font-semibold leading-tight text-slate-950 sm:text-4xl"
                >
                    {{ $section->heading }}
                </h2>
                @if ($section->summary)
                    <p class="mt-4 max-w-md text-sm leading-7 text-slate-600">
                        {{ $section->summary }}
                    </p>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($section->features as $feature)
                    <article
                        class="{{ $loop->first ? 'md:col-span-2 md:grid md:grid-cols-[0.9fr_1.1fr] md:items-stretch' : '' }} overflow-hidden rounded-[0.35rem] border border-slate-200 bg-[#f7f8f6]"
                    >
                        @if (! empty($feature['image']))
                            <img
                                src="{{ $feature['image'] }}"
                                alt=""
                                class="{{ $loop->first ? 'aspect-[16/10] md:h-full' : 'aspect-[4/3]' }} w-full object-cover"
                            />
                        @endif

                        <div class="p-5 sm:p-6">
                            <div
                                class="mb-5 flex items-center gap-3 text-xs text-slate-500"
                            >
                                <span
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-300 text-slate-700"
                                >
                                    {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                                </span>
                                @if (! empty($feature['type']))
                                    <span>{{ $feature['type'] }}</span>
                                @endif
                            </div>
                            <h3 class="text-lg font-semibold text-slate-950">
                                {{ $feature['title'] }}
                            </h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                {{ $feature['description'] ?? $feature['summary'] ?? '' }}
                            </p>
                            @if (! empty($feature['publishedDate']) || ! empty($feature['author']))
                                <p class="mt-5 text-xs text-slate-500">
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
