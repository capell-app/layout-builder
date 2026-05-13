@props([
    'asset',
    'class' => null,
    'color' => null,
    'icon' => null,
    'image' => null,
    'linkText' => null,
    'loop' => null,
    'meta' => [],
    'size' => null,
    'summary' => null,
    'title' => null,
    'url' => null,
])

@php
    $name = $title ?: $asset->translation?->title;
    $position = $asset->getMeta('position');
    $bio = $summary ?: strip_tags((string) $asset->translation?->content);
    $initials = collect(explode(' ', (string) $name))
        ->filter()
        ->map(static fn (string $part): string => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('');

    $classList = collect([
        'team-member-card flex h-full flex-col rounded-lg bg-white p-6 text-center shadow-sm ring-1 ring-black/5',
    ])
        ->merge(is_array($class) ? $class : [$class])
        ->filter()
        ->implode(' ');
@endphp

<article {{ $attributes->except('class')->merge(['class' => $classList]) }}>
    <div
        class="mx-auto mb-4 flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-gray-100 text-xl font-semibold text-gray-700"
    >
        @if ($image)
            <x-capell::media
                :media="$image"
                loading="lazy"
                :alt="$name"
                class="h-full w-full object-cover"
            />
        @else
            {{ $initials }}
        @endif
    </div>

    @if ($name)
        <h3 class="text-lg font-semibold text-gray-950">
            @if ($url)
                <a href="{{ $url }}" @wireNavigate>
                    {{ $name }}
                </a>
            @else
                {{ $name }}
            @endif
        </h3>
    @endif

    @if ($position)
        <p class="text-primary mt-1 text-sm font-medium">
            {{ $position }}
        </p>
    @endif

    @if ($bio)
        <p class="mt-3 text-sm leading-6 text-gray-600">
            {{ $bio }}
        </p>
    @endif
</article>
