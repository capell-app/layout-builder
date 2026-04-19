@props([
    'locales' => [],
    'active' => null,
    'routeName' => null,
    'variant' => 'dropdown', // dropdown | inline
])

@php
    $activeCode = $active ?? app()->getLocale();
@endphp

@if ($variant === 'inline')
    <ul class="capell-lang-switcher flex items-center gap-3 text-sm" role="list">
        @foreach ($locales as $code => $meta)
            <li>
                <a
                    href="{{ $meta['href'] ?? ('?lang=' . $code) }}"
                    hreflang="{{ $code }}"
                    lang="{{ $code }}"
                    @if ($code === $activeCode) aria-current="true" @endif
                    class="uppercase tracking-wide hover:underline {{ $code === $activeCode ? 'font-semibold' : '' }}"
                >
                    {{ $meta['short'] ?? strtoupper($code) }}
                </a>
            </li>
        @endforeach
    </ul>
@else
    <div
        x-data="{ open: false }"
        class="capell-lang-switcher relative inline-block text-left"
    >
        <button
            type="button"
            aria-haspopup="listbox"
            :aria-expanded="open.toString()"
            @click="open = !open"
            @click.outside="open = false"
            class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-1.5 text-sm"
        >
            <span aria-hidden="true">{{ $locales[$activeCode]['short'] ?? strtoupper($activeCode) }}</span>
            <span class="sr-only">{{ __('Change language') }}</span>
            <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
            </svg>
        </button>

        <ul
            x-show="open"
            x-cloak
            role="listbox"
            class="absolute right-0 z-20 mt-2 min-w-[10rem] rounded-md border border-gray-100 bg-white py-1 shadow-lg"
        >
            @foreach ($locales as $code => $meta)
                <li role="option" aria-selected="{{ $code === $activeCode ? 'true' : 'false' }}">
                    <a
                        href="{{ $meta['href'] ?? ('?lang=' . $code) }}"
                        hreflang="{{ $code }}"
                        lang="{{ $code }}"
                        class="block px-3 py-2 text-sm hover:bg-gray-50 {{ $code === $activeCode ? 'font-semibold' : '' }}"
                    >
                        {{ $meta['native'] ?? ($meta['name'] ?? strtoupper($code)) }}
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
