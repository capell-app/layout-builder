<section class="border-b border-slate-200/80 bg-white">
    <div class="mx-auto max-w-7xl px-5 py-16 sm:px-6 lg:py-20">
        <div
            class="grid gap-8 border-y border-slate-200 py-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center"
        >
            <h2
                class="max-w-xl text-3xl font-semibold leading-tight text-slate-950 sm:text-4xl"
            >
                {{ $section->heading }}
            </h2>
            <div>
                @if ($section->summary)
                    <p class="max-w-2xl text-sm leading-7 text-slate-600">
                        {{ $section->summary }}
                    </p>
                @endif

                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($section->actions as $action)
                        <a
                            href="{{ $action['url'] }}"
                            class="rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-[var(--theme-primary)]"
                        >
                            {{ $action['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
