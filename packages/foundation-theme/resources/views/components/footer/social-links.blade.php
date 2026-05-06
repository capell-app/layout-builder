@props([
    'links',
    'size' => 'md',
])
<div
    {{
        $attributes->class([
            'footer-social-links inline-flex flex-wrap items-center gap-2',
        ])
    }}
>
    @foreach ($links as $link)
        <a
            class="hover:text-primary focus:text-primary group/item flex items-center gap-x-1"
            href="{{ $link['url'] }}"
            target="_blank"
            rel="nofollow"
        >
            @if (! empty($link['icon']))
                @svg($link['icon'], [
                    'class' => 'shrink-0 grow-0 opacity-50 group-hover/item:opacity-100' . match ($size) {
                        'sm' => ' h-5 w-5',
                        'md' => ' h-8 w-8',
                        'lg' => ' h-10 w-10',
                    },
                    'title' => $link['title'] ?? $link['type'],
                ])
            @elseif (! empty($link['file']))
                @php
                    $image = is_array($link['file']) ? collect($link['file'])->first() : $link['file'];
                @endphp

                <img
                    @class([
                        'shrink-0 grow-0 overflow-hidden text-center leading-none brightness-0 contrast-[.5] invert sepia-0 filter group-hover/item:contrast-150 group-focus/item:contrast-150',
                        'h-6 w-6' => $size === 'xs',
                        'h-8 w-8' => $size === 'sm',
                        'h-10 w-10' => $size === 'md',
                        'h-12 w-12' => $size === 'lg',
                    ])
                    src="{{ asset('storage/' . $image) }}"
                    alt="{{ $link['title'] ?: $link['type'] }}"
                    loading="lazy"
                />
            @endif

            @if (! empty($link['title']))
                <span
                    class="inline-block text-balance text-left text-sm font-medium leading-none"
                >
                    {{ $link['title'] ?? str($link['type'])->title() }}
                </span>
            @endif
        </a>
    @endforeach
</div>
