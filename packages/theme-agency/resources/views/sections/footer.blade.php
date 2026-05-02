<footer class="bg-zinc-950 text-white">
    <div
        class="mx-auto grid max-w-7xl gap-8 px-6 py-14 md:grid-cols-[1.3fr_2fr]"
    >
        <div>
            <p class="text-2xl font-black">{{ $section->brandName }}</p>
            @if ($section->summary)
                <p class="mt-3 text-sm text-white/60">
                    {{ $section->summary }}
                </p>
            @endif
        </div>
        <div class="grid gap-6 sm:grid-cols-3">
            @foreach ($section->columns as $column)
                <div>
                    <h3 class="text-sm font-bold text-[var(--theme-accent)]">
                        {{ $column['heading'] }}
                    </h3>
                    <ul class="mt-3 space-y-2 text-sm text-white/65">
                        @foreach ($column['links'] as $link)
                            <li>
                                <a
                                    href="{{ $link['url'] }}"
                                    class="hover:text-white"
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
