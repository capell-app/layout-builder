<section
    class="border-b border-slate-200/80 bg-white dark:border-white/10 dark:bg-slate-900"
>
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:py-16">
        <div
            class="grid gap-5 border-y border-slate-200 py-7 sm:py-9 lg:grid-cols-[0.9fr_1.1fr] lg:items-center dark:border-white/10"
        >
            <h2
                class="max-w-xl text-2xl font-semibold leading-tight text-slate-950 sm:text-3xl lg:text-4xl dark:text-white"
            >
                {{ $section->heading }}
            </h2>
            <div>
                @if ($section->summary)
                    <p
                        class="max-w-2xl text-sm leading-6 text-slate-600 sm:leading-7 dark:text-slate-300"
                    >
                        {{ $section->summary }}
                    </p>
                @endif

                <div class="mt-5 flex flex-wrap gap-2 sm:mt-6 sm:gap-3">
                    @foreach ($section->actions as $action)
                        <a
                            href="{{ $action['url'] }}"
                            class="rounded-full bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[var(--theme-primary)] sm:px-5 sm:py-3 dark:bg-white dark:text-slate-950 dark:hover:bg-[var(--theme-accent)]"
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
