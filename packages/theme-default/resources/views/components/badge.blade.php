@props([
    'count' => null,
    'size' => 'md',
    'url' => null,
    'active' => false,
])

@php
    $classes = [
        'group inline-flex items-center justify-between gap-2 rounded-full pl-3 pr-2',
        'py-1 text-xs' => $size === 'sm',
        'py-1.5 text-sm' => $size === 'md',
        'py-2 text-base' => $size === 'lg',
        'bg-gray-100 font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200',
        'hover:text-primary focus:text-primary cursor-pointer transition hover:bg-gray-200 dark:hover:bg-gray-900' => $url,
        'ring-primary-500 ring-2' => $active,
    ];
@endphp

@capellBuffer($content)
    <span class="truncate">
        {{ $slot }}
    </span>
    @if ($count)
        <span
            class="group-hover:bg-primary group-focus:bg-primary dark:group-hover:bg-primary-600 dark:group-focus:bg-primary-600 rounded-full bg-gray-300 px-2 py-0.5 text-xs font-light text-gray-600 group-hover:text-white group-focus:text-white dark:bg-gray-700 dark:text-gray-300 dark:group-hover:text-white dark:group-focus:text-white"
        >
            {{ $count }}
        </span>
    @endif

    @if (isset($icon))
        <span
            class="ml-1 group-hover:text-white group-focus:text-white dark:group-hover:text-white dark:group-focus:text-white"
        >
            {!! $icon !!}
        </span>
    @endif
@endcapellBuffer

@if ($url)
    <a href="{{ $url }}" @class($classes) wire:navigate>
        {{ $content() }}
    </a>
@else
    <span @class($classes)>
        {{ $content() }}
    </span>
@endif
