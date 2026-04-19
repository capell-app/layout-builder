@props(['target' => '#main-content', 'label' => 'Skip to main content'])
<a
    href="{{ $target }}"
    class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-gray-900 focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
    tabindex="1"
>
    {{ $label }}
</a>
