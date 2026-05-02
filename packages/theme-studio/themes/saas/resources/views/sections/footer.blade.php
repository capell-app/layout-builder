<footer class="border-t border-slate-200">
    <div class="mx-auto grid max-w-7xl gap-8 px-6 py-12 md:grid-cols-[1fr_2fr]">
        <div>
            <p class="font-bold text-[var(--theme-primary)]">
                {{ $section->brandName }}
            </p>
            @if ($section->summary)
                <p class="mt-3 text-sm text-slate-600">
                    {{ $section->summary }}
                </p>
            @endif
        </div>
        <div class="grid gap-6 sm:grid-cols-3">
            @foreach ($section->columns as $column)
                <div>
                    <h3 class="text-sm font-semibold">
                        {{ $column['heading'] }}
                    </h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        @foreach ($column['links'] as $link)
                            <li>
                                <a
                                    href="{{ $link['url'] }}"
                                    class="hover:text-[var(--theme-primary)]"
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
