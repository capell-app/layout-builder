@props ([
    'class' => 'h-4 w-4',
])

<svg
    {{ $attributes->class([$class, 'shrink-0'])->merge(['aria-hidden' => 'true', 'fill' => 'currentColor', 'viewBox' => '0 0 10 16']) }}
>
    <circle
        cx="3"
        cy="3"
        r="1.25"
    />
    <circle
        cx="7"
        cy="3"
        r="1.25"
    />
    <circle
        cx="3"
        cy="8"
        r="1.25"
    />
    <circle
        cx="7"
        cy="8"
        r="1.25"
    />
    <circle
        cx="3"
        cy="13"
        r="1.25"
    />
    <circle
        cx="7"
        cy="13"
        r="1.25"
    />
</svg>
