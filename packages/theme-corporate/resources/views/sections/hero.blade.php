@php
    $actions = $section->actions !== []
        ? $section->actions
        : [
            ['label' => 'Explore content', 'url' => '#content', 'style' => 'primary'],
            ['label' => 'View media', 'url' => '#gallery', 'style' => 'secondary'],
        ];
@endphp

<section
    class="border-b border-slate-200/80 bg-[#f7f8f6] dark:border-white/10 dark:bg-slate-950"
>
    <div
        class="lg:py-18 mx-auto grid max-w-7xl items-end gap-5 px-4 py-6 sm:px-6 sm:py-10 md:py-14 lg:grid-cols-[0.88fr_1.12fr] lg:gap-10"
    >
        <div class="space-y-4 sm:space-y-5">
            <div
                class="flex flex-wrap items-center gap-2 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400"
            >
                <span
                    class="text-[var(--theme-primary)] dark:text-[var(--theme-accent)]"
                >
                    {{ $section->eyebrow ?: 'Featured' }}
                </span>

                @if ($section->mediaAlt)
                    <span class="h-px w-6 bg-slate-300 dark:bg-white/15"></span>
                    <span>{{ $section->mediaAlt }}</span>
                @endif
            </div>

            <h1
                class="max-w-4xl text-4xl font-semibold leading-none text-slate-950 sm:text-5xl lg:text-7xl dark:text-white"
            >
                {{ $section->heading }}
            </h1>

            @if ($section->summary)
                <p
                    class="max-w-xl text-sm leading-6 text-slate-600 sm:text-base sm:leading-7 lg:text-lg lg:leading-8 dark:text-slate-300"
                >
                    {{ $section->summary }}
                </p>
            @endif

            <div class="flex flex-wrap gap-2 sm:gap-3">
                @foreach ($actions as $action)
                    <a
                        href="{{ $action['url'] }}"
                        class="{{ ($action['style'] ?? 'primary') === 'secondary' ? 'border border-slate-300 text-slate-800 hover:border-slate-950 dark:border-white/15 dark:text-slate-200 dark:hover:border-white' : 'bg-[var(--theme-accent)] text-slate-950 hover:bg-white' }} rounded-full px-3.5 py-2 text-xs font-semibold transition sm:px-5 sm:py-3 sm:text-sm"
                    >
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>

            <dl
                class="grid grid-cols-3 gap-2 border-t border-slate-200 pt-4 text-xs sm:max-w-xl sm:gap-4 sm:pt-5 dark:border-white/10"
            >
                <div>
                    <dt
                        class="font-semibold uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500"
                    >
                        Pages
                    </dt>
                    <dd
                        class="mt-1 font-semibold text-slate-900 dark:text-white"
                    >
                        Content
                    </dd>
                </div>
                <div>
                    <dt
                        class="font-semibold uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500"
                    >
                        Media
                    </dt>
                    <dd
                        class="mt-1 font-semibold text-slate-900 dark:text-white"
                    >
                        Assets
                    </dd>
                </div>
                <div>
                    <dt
                        class="font-semibold uppercase tracking-[0.12em] text-slate-400 dark:text-slate-500"
                    >
                        Layout
                    </dt>
                    <dd
                        class="mt-1 font-semibold text-slate-900 dark:text-white"
                    >
                        Widgets
                    </dd>
                </div>
            </dl>
        </div>

        @if ($section->mediaUrl)
            <figure class="relative">
                <img
                    src="{{ $section->mediaUrl }}"
                    alt="{{ $section->mediaAlt ?? '' }}"
                    class="aspect-[16/10] max-h-[18rem] w-full rounded-[0.35rem] object-cover sm:aspect-[5/4] sm:max-h-none"
                />
                <figcaption
                    class="mt-2 flex items-center justify-between gap-3 text-[0.68rem] uppercase tracking-[0.14em] text-slate-500 sm:mt-3 sm:gap-4 sm:text-xs sm:tracking-[0.16em] dark:text-slate-400"
                >
                    <span>{{ $section->mediaAlt ?? $section->heading }}</span>
                    <span
                        class="h-px grow bg-slate-300 dark:bg-white/15"
                    ></span>
                </figcaption>
            </figure>
        @endif
    </div>
</section>
