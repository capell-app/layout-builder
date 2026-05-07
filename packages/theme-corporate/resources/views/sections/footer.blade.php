<footer class="bg-slate-950 text-white">
    <div
        class="mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:px-6 md:grid-cols-[1.2fr_2fr]"
    >
        <div>
            <p class="text-sm font-semibold tracking-wide">
                {{ $section->brandName }}
            </p>
            @if ($section->summary)
                <p class="mt-3 max-w-sm text-sm leading-6 text-slate-400">
                    {{ $section->summary }}
                </p>
            @endif
        </div>
        <div class="grid gap-6 sm:grid-cols-3">
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
