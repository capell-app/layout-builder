<?php

declare(strict_types=1);

?>

@props([
    'title',
    'summary',
    'color' => null,
    'image' => null,
    'icon' => null,
    'loop' => null,
    'tags' => null,
    'linkText' => null,
    'url' => null,
    'withSummary' => true,
])
<div
    @class([
        'md:p-y-16 flex flex-col items-center space-y-6 p-10 text-center',
        match ($color) {
            'danger' => 'bg-danger text-white',
            'dark-gray' => 'bg-dark-gray text-white',
            'gray' => 'bg-gray text-white',
            'info' => 'bg-info text-white',
            'light-gray' => 'bg-light-gray text-gray-900',
            'primary' => 'bg-primary text-white',
            'secondary' => 'bg-secondary text-white',
            'success' => 'bg-success text-white',
            'warning' => 'bg-warning text-white',
            'white' => 'bg-white text-gray-900',
        },
    ])
>
    @if ($icon)
        <div>
            <x-capell::icon
                :$icon
                class="h-10 w-10"
            />
        </div>
    @endif

    <h2 class="space-y-8">
        @if ($withSummary && $summary)
            <span class="mb-1 block text-2xl font-bold tracking-tight">
                {!! $summary !!}
            </span>
        @endif

        @if ($title)
            @if ($url)
                <a
                    href="{{ $url }}"
                    class="text-3xl font-bold tracking-tight hover:underline"
                >
                    <span class="text-base font-medium">{{ $title }}</span>
                </a>
            @else
                <span class="text-base font-medium">{{ $title }}</span>
            @endif
        @endif
    </h2>
</div>

<?php
