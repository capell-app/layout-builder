<a
    {{
        $attributes->merge([
            'class' => 'block px-4 py-2 text-xs font-semibold leading-tight text-gray-700 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 transition',
        ])
    }}
    wire:navigate
>
    {{ $slot }}
</a>
