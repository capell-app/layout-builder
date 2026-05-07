<footer id="footer" class="bg-slate-950 text-white dark:bg-black">
    <div
        class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 sm:py-12 md:grid-cols-[1.2fr_2fr]"
    >
        <div>
            <p
                class="inline-flex items-center gap-2.5 text-sm font-semibold tracking-wide"
            >
                <span
                    class="inline-flex h-8 w-8 items-center justify-center rounded-[0.35rem] bg-white text-[0.7rem] font-bold uppercase text-slate-950"
                >
                    {{ collect(explode(' ', trim($section->brandName)))->filter()->map(fn (string $word): string => mb_substr($word, 0, 1))->take(2)->implode('') ?: 'C' }}
                </span>
                <span>{{ $section->brandName }}</span>
            </p>
            @if ($section->summary)
                <p class="mt-3 max-w-sm text-sm leading-6 text-slate-400">
                    {{ $section->summary }}
                </p>
            @endif
        </div>
        <div class="grid gap-5 sm:grid-cols-3 sm:gap-6">
            @foreach ($section->columns as $column)
                <div>
                    <h3 class="text-sm font-semibold text-white">
                        {{ $column['heading'] }}
                    </h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-400">
                        @foreach ($column['links'] as $link)
                            <li>
                                <a
                                    href="{{ $link['url'] }}"
                                    class="transition hover:text-white"
                                >
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</footer>
