@props([
    'locales' => ['en' => 'English', 'fr' => 'Français', 'es' => 'Español'],
    'current' => null,
])

@php
    $current = $current ?? (function_exists('app') ? app()->getLocale() : 'en');
@endphp

<div class="relative">
    <label
        for="language-switcher"
        class="sr-only"
    >
        Change language
    </label>
    <select
        id="language-switcher"
        name="locale"
        aria-label="Change language"
        class="appearance-none rounded-md border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-1 text-sm"
    >
        @foreach ($locales as $code => $label)
            <option
                value="{{ $code }}"
                @selected($code === $current)
            >
                {{ $label }}
            </option>
        @endforeach
    </select>
    {{ $slot ?? '' }}
</div>
