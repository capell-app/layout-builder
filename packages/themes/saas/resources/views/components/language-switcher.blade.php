@props([
    'locales' => ['en' => 'English', 'fr' => 'Français', 'es' => 'Español', 'de' => 'Deutsch'],
    'current' => null,
])

@php
    $current = $current ?? (function_exists('app') ? app()->getLocale() : 'en');
@endphp

<div class="relative">
    <label for="saas-language-switcher" class="sr-only">Change language</label>
    <select id="saas-language-switcher"
            name="locale"
            aria-label="Change language"
            class="appearance-none rounded-lg border border-[var(--color-border)] bg-[var(--color-bg)] px-3 py-1.5 text-sm text-[var(--color-fg)]">
        @foreach ($locales as $code => $label)
            <option value="{{ $code }}" @selected($code === $current)>{{ $label }}</option>
        @endforeach
    </select>
    {{ $slot ?? '' }}
</div>
