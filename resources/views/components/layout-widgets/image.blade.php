@props ([
    'src',
    'alt' => null,
    'loading' => 'lazy',
])

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    loading="{{ $loading }}"
    class="capell-component capell-widgets-image mx-auto h-auto max-w-full"
/>
