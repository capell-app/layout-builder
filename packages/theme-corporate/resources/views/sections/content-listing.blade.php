@php
    $variant = $section->variant ?? 'editorial';
@endphp

<section class="border-b border-slate-200/80 bg-[#f7f8f6]">
    <div class="mx-auto max-w-7xl px-5 py-16 sm:px-6 lg:py-20">
        <div class="mb-10 grid gap-6 lg:grid-cols-[0.75fr_1.25fr] lg:items-end">
            <div>
                <p
                    class="mb-4 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--theme-primary)]"
                >
                    {{ ucfirst($variant) }}
                </p>
                <h2
                    class="max-w-xl text-3xl font-semibold leading-tight text-slate-950 sm:text-4xl"
                >
                    {{ $section->heading }}
                </h2>
            </div>
            @if ($section->summary)
                <p
                    class="max-w-2xl text-sm leading-7 text-slate-600 lg:justify-self-end"
                >
                    {{ $section->summary }}
                </p>
            @endif
        </div>

        @if ($variant === 'gallery')
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="{{ $loop->first ? 'sm:col-span-2 sm:row-span-2' : '' }} group relative block overflow-hidden rounded-[0.35rem] bg-slate-200"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                alt=""
                                class="{{ $loop->first ? 'aspect-square' : 'aspect-[4/3]' }} w-full object-cover transition duration-300 group-hover:scale-[1.025]"
                            />
                        @else
                            <div
                                class="{{ $loop->first ? 'aspect-square' : 'aspect-[4/3]' }} w-full bg-slate-200"
                            ></div>
                        @endif
                        <div
                            class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/80 to-transparent p-4 text-white"
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
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="group block overflow-hidden rounded-[0.35rem] border border-slate-200 bg-white"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                alt=""
                                class="aspect-video w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                            />
                        @endif

                        <div class="flex items-start justify-between gap-4 p-5">
                            <div>
                                <h3 class="font-semibold text-slate-950">
                                    {{ $item['title'] }}
                                </h3>
                                @if (! empty($item['summary']))
                                    <p
                                        class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600"
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
                class="divide-y divide-slate-200 rounded-[0.35rem] border border-slate-200 bg-white"
            >
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="grid gap-3 p-5 transition hover:bg-slate-50 sm:grid-cols-[0.25fr_1fr] sm:p-6"
                    >
                        <span
                            class="text-xs font-medium uppercase tracking-[0.16em] text-slate-400"
                        >
                            Question {{ $loop->iteration }}
                        </span>
                        <span>
                            <span class="block font-semibold text-slate-950">
                                {{ $item['title'] }}
                            </span>
                            @if (! empty($item['summary']))
                                <span
                                    class="mt-2 block text-sm leading-6 text-slate-600"
                                >
                                    {{ $item['summary'] }}
                                </span>
                            @endif
                        </span>
                    </a>
                @endforeach
            </div>
        @elseif ($variant === 'pricing')
            <div class="grid gap-4 md:grid-cols-3">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="block rounded-[0.35rem] border border-slate-200 bg-white p-6 transition hover:-translate-y-0.5 hover:border-slate-950"
                    >
                        <p
                            class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--theme-primary)]"
                        >
                            {{ $item['meta'][0] ?? $item['type'] ?? 'Plan' }}
                        </p>
                        <h3 class="mt-4 text-xl font-semibold text-slate-950">
                            {{ $item['title'] }}
                        </h3>
                        @if (! empty($item['summary']))
                            <p class="mt-4 text-sm leading-6 text-slate-600">
                                {{ $item['summary'] }}
                            </p>
                        @endif

                        <span
                            class="mt-6 inline-flex rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                        >
                            View detail
                        </span>
                    </a>
                @endforeach
            </div>
        @elseif ($variant === 'people')
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="flex gap-4 rounded-[0.35rem] border border-slate-200 bg-white p-4 transition hover:border-slate-950"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                alt=""
                                class="h-20 w-20 shrink-0 rounded-[0.25rem] object-cover"
                            />
                        @endif

                        <span class="min-w-0">
                            <span class="block font-semibold text-slate-950">
                                {{ $item['title'] }}
                            </span>
                            <span class="mt-1 block text-sm text-slate-500">
                                {{ $item['meta'][0] ?? $item['type'] ?? $item['author'] ?? '' }}
                            </span>
                            @if (! empty($item['summary']))
                                <span
                                    class="mt-2 line-clamp-2 block text-sm leading-6 text-slate-600"
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
                        class="block rounded-[0.35rem] border border-slate-200 bg-white p-6"
                    >
                        <span class="text-4xl font-semibold text-slate-950">
                            {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        </span>
                        <h3 class="mt-6 font-semibold text-slate-950">
                            {{ $item['title'] }}
                        </h3>
                        @if (! empty($item['summary']))
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                {{ $item['summary'] }}
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-[1.05fr_0.95fr]">
                @foreach ($section->items as $item)
                    <a
                        href="{{ $item['url'] ?? '#' }}"
                        class="{{ $loop->first ? 'lg:row-span-3' : 'lg:grid lg:grid-cols-[8rem_1fr]' }} group overflow-hidden rounded-[0.35rem] border border-slate-200 bg-white transition hover:border-slate-950"
                    >
                        @if (! empty($item['image']))
                            <img
                                src="{{ $item['image'] }}"
                                alt=""
                                class="{{ $loop->first ? 'aspect-[16/10]' : 'aspect-[4/3] lg:h-full' }} w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                            />
                        @endif

                        <span class="block p-5 sm:p-6">
                            <span
                                class="mb-4 flex flex-wrap items-center gap-2 text-xs text-slate-500"
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
                                class="{{ $loop->first ? 'text-2xl' : 'text-base' }} block font-semibold leading-tight text-slate-950"
                            >
                                {{ $item['title'] }}
                            </span>
                            @if (! empty($item['summary']))
                                <span
                                    class="mt-3 block text-sm leading-6 text-slate-600"
                                >
                                    {{ $item['summary'] }}
                                </span>
                            @endif

                            @if (! empty($item['meta']))
                                <span class="mt-5 flex flex-wrap gap-2">
                                    @foreach ($item['meta'] as $label)
                                        <span
                                            class="rounded-full border border-slate-200 px-2.5 py-1 text-xs text-slate-500"
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
