@if ($asset !== null)
    <img
        src="{{ asset($asset) }}"
        alt="{{ $fallbackLabel }}"
        {{ $attributes->class(['w-5 border border-gray-200 dark:border-none']) }}
    />
@elseif ($fallbackLabel !== '')
    <span
        {{ $attributes->class(['text-xs text-gray-500 dark:text-gray-400']) }}
    >
        {{ $fallbackLabel }}
    </span>
@endif
