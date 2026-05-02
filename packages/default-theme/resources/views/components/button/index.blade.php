@props(['url' => '', 'title' => ''])

@if ($url)
    <a href="{{ $url }}" title="{{ strip_tags($title) }}" {{ $attributes }}>
        {{ $slot }}
    </a>
@else
    <button title="{{ strip_tags($title) }}" {{ $attributes }}>
        {{ $slot }}
    </button>
@endif
