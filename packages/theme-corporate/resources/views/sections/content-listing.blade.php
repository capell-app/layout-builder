@php
    $variant = $section->variant ?? 'editorial';
    $sectionId = $variant === 'gallery' || $variant === 'media' ? 'gallery' : 'content';
@endphp

<section
    id="{{ $sectionId }}"
    class="border-b border-slate-200/80 bg-[#f7f8f6] dark:border-white/10 dark:bg-slate-950"
>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:py-16">
        <div
            class="mb-6 grid gap-3 sm:mb-8 lg:grid-cols-[0.75fr_1.25fr] lg:items-end"
        >
            <div>
                <p
                    class="mb-3 text-xs font-semibold uppercase tracking-[0.16em] text-[var(--theme-primary)] dark:text-[var(--theme-accent)]"
                >
                    {{ ucfirst($variant) }}
                </p>
                <h2
                    class="max-w-xl text-2xl font-semibold leading-tight text-slate-950 sm:text-3xl lg:text-4xl dark:text-white"
                >
                    {{ $section->heading }}
                </h2>
            </div>
            @if ($section->summary)
                <p
                    class="max-w-2xl text-sm leading-6 text-slate-600 sm:leading-7 lg:justify-self-end dark:text-slate-300"
                >
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        @if ($variant === 'gallery')
            <div class="grid gap-2 sm:grid-cols-2 sm:gap-3 lg:grid-cols-4">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="{{ $loop->first ? 'sm:col-span-2 sm:row-span-2' : '' }} group relative block overflow-hidden rounded-[0.35rem] bg-slate-200 dark:bg-slate-800"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                alt=""
                                class="{{ $loop->first ? 'aspect-[4/3] sm:aspect-square' : 'aspect-[5/3] sm:aspect-[4/3]' }} w-full object-cover transition duration-300 group-hover:scale-[1.025]"
                            />
                        @else
                            <div
                                class="{{ $loop->first ? 'aspect-[4/3] sm:aspect-square' : 'aspect-[5/3] sm:aspect-[4/3]' }} w-full bg-slate-200 dark:bg-slate-800"
                            ></div>
                        @endif
                        <div
                            class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/80 to-transparent p-3 text-white sm:p-4"
                        >
                            <h3 class="text-sm font-semibold">
                                {{ $item['title'] }}
                            </h3>
                            @if (! empty($item['type']))
                                <p class="mt-1 text-xs text-white/75">
                                    {{ $item['type'] }}
                                </p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        @elseif ($variant === 'media')
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="group block overflow-hidden rounded-[0.35rem] border border-slate-200 bg-white dark:border-white/10 dark:bg-white/[0.03]"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                alt=""
                                class="aspect-[5/3] w-full object-cover transition duration-300 group-hover:scale-[1.02] sm:aspect-video"
                            />
                        @endif

                        <div
                            class="flex items-start justify-between gap-3 p-4 sm:p-5"
                        >
                            <div>
                                <h3
                                    class="font-semibold text-slate-950 dark:text-white"
                                >
                                    {{ $item['title'] }}
                                </h3>
                                @if (! empty($item['summary']))
                                    <p
                                        class="mt-1.5 line-clamp-2 text-sm leading-6 text-slate-600 dark:text-slate-300"
                                    >
                                        {{ $item['summary'] }}
                                    </p>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400">
                                {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        @elseif ($variant === 'faq')
            <div
                class="divide-y divide-slate-200 rounded-[0.35rem] border border-slate-200 bg-white dark:divide-white/10 dark:border-white/10 dark:bg-white/[0.03]"
            >
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="grid gap-2 p-4 transition hover:bg-slate-50 sm:grid-cols-[0.25fr_1fr] sm:p-5 dark:hover:bg-white/[0.04]"
                    >
                        <span
                            class="text-xs font-medium uppercase tracking-[0.16em] text-slate-400"
                        >
                            Question {{ $loop->iteration }}
                        </span>
                        <span>
                            <span
                                class="block font-semibold text-slate-950 dark:text-white"
                            >
                                {{ $item['title'] }}
                            </span>
                            @if (! empty($item['summary']))
                                <span
                                    class="mt-1.5 block text-sm leading-6 text-slate-600 dark:text-slate-300"
                                >
                                    {{ $item['summary'] }}
                                </span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @elseif ($variant === 'people')
            <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="flex gap-3 rounded-[0.35rem] border border-slate-200 bg-white p-3.5 transition hover:border-slate-950 sm:gap-4 sm:p-4 dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-white"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                alt=""
                                class="h-16 w-16 shrink-0 rounded-[0.25rem] object-cover sm:h-20 sm:w-20"
                            />
                        @endif

                        <span class="min-w-0">
                            <span
                                class="block font-semibold text-slate-950 dark:text-white"
                            >
                                {{ $item['title'] }}
                            </span>
                            <span
                                class="mt-1 block text-sm text-slate-500 dark:text-slate-400"
                            >
                                {{ $item['meta'][0] ?? $item['type'] ?? $item['author'] ?? '' }}
                            </span>
                            @if (! empty($item['summary']))
                                <span
                                    class="mt-1.5 line-clamp-2 block text-sm leading-6 text-slate-600 dark:text-slate-300"
                                >
                                    {{ $item['summary'] }}
                                </span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @elseif ($variant === 'metrics')
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="block rounded-[0.35rem] border border-slate-200 bg-white p-5 sm:p-6 dark:border-white/10 dark:bg-white/[0.03]"
                    >
                        <span
                            class="text-3xl font-semibold text-slate-950 sm:text-4xl dark:text-white"
                        >
                            {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <h3
                            class="mt-4 font-semibold text-slate-950 sm:mt-6 dark:text-white"
                        >
                            {{ $item['title'] }}
                        </h3>
                        @if (! empty($item['summary']))
                            <p
                                class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300"
                            >
                                {{ $item['summary'] }}
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <div class="grid gap-3 lg:grid-cols-[1.05fr_0.95fr] lg:gap-4">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="{{ $loop->first ? 'lg:row-span-3' : 'grid grid-cols-[6.5rem_1fr] lg:grid-cols-[8rem_1fr]' }} group overflow-hidden rounded-[0.35rem] border border-slate-200 bg-white transition hover:border-slate-950 dark:border-white/10 dark:bg-white/[0.03] dark:hover:border-white"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                alt=""
                                class="{{ $loop->first ? 'aspect-[5/3] sm:aspect-[16/10]' : 'h-full min-h-28 sm:aspect-[4/3] lg:h-full' }} w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                            />
                        @endif

                        <span class="block p-4 sm:p-5 lg:p-6">
                            <span
                                class="mb-2 flex flex-wrap items-center gap-2 text-xs text-slate-500 sm:mb-3 lg:mb-4 dark:text-slate-400"
                            >
                                @if (! empty($item['type']))
                                    <span>{{ $item['type'] }}</span>
                                @endif

                                @if (! empty($item['publishedAt']) && ! empty($item['publishedDate']))
                                    <time
                                        datetime="{{ $item['publishedAt'] }}"
                                    >
                                        {{ $item['publishedDate'] }}
                                    </time>
                                @endif

                                @if (! empty($item['author']))
                                    <span>{{ $item['author'] }}</span>
                                @endif
                            </span>
                            <span
                                class="{{ $loop->first ? 'text-xl sm:text-2xl' : 'text-base' }} block font-semibold leading-tight text-slate-950 dark:text-white"
                            >
                                {{ $item['title'] }}
                            </span>
                            @if (! empty($item['summary']))
                                <span
                                    class="mt-2 line-clamp-2 block text-sm leading-6 text-slate-600 sm:line-clamp-3 dark:text-slate-300"
                                >
                                    {{ $item['summary'] }}
                                </span>
                            @endif

                            @if (! empty($item['meta']))
                                <span class="mt-3 flex flex-wrap gap-2 sm:mt-5">
                                    @foreach ($item['meta'] as $label)
                                        <span
                                            class="rounded-full border border-slate-200 px-2.5 py-1 text-xs text-slate-500 dark:border-white/10 dark:text-slate-300"
                                        >
                                            {{ $label }}
                                        </span>
                                    @endforeach
                                </span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</section>
