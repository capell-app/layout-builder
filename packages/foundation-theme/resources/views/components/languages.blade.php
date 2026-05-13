<?php
use Capell\Frontend\Facades\Frontend;

$page = Frontend::page();
$language = Frontend::language();
$site = Frontend::site();
$theme = Frontend::theme();

?>

@props([
    'linkClass' => 'dropdown-item hover:text-primary focus:text-primary block w-full whitespace-nowrap bg-transparent px-4 py-3 text-left text-base text-sm font-medium leading-none',
    'name' => 'languages',
    'dropdownLabelClass' => '',
    'darkMode' => false,
    'align' => 'left',
    'theme' => 'light',
    'languages',
])
<x-capell::dropdown
    name="{{ $name }}"
    :attributes="$attributes->class(['inline-flex'])"
    :label="__('capell-frontend::generic.languages')"
    :dark-mode="$darkMode"
    :placement="$align === 'right' ? 'top-end' : 'top-start'"
    role="menu"
>
    <x-slot:trigger
        @class([
            'hover:text-primary focus:text-primary focus:ring-primary/50 dark:focus:ring-primary cursor-pointer gap-2 rounded-md border border-white/10 px-4 py-1.5 focus:outline-none focus:ring-2',
            'dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800' => $darkMode && $theme->withDarkMode,
        ])
    >
        <img
            class="h-4 w-4"
            src="{{ asset("vendor/blade-country-flags/4x3-{$language->flag}.svg") }}"
            alt="{{ $language->name }}"
            x-tooltip.raw="{{ $language->name }}"
            title="{{ $language->name }}"
            loading="lazy"
        />
        <span class="{{ $dropdownLabelClass }}">
            <span>{{ $language->name }}</span>
            @svg('heroicon-m-chevron-down', 'inline h-4 w-4')
        </span>
    </x-slot>

    @foreach ($languages as $siteLanguage)
        @continue($siteLanguage['id'] === $language->id)
        <div>
            <a
                href="{{ $siteLanguage['url'] }}"
                role="menuitem"
                tabindex="-1"
                class="{{ $linkClass }}"
                @wireNavigate
            >
                <img
                    class="mr-2 inline-block h-4 w-4 align-top"
                    src="{{ asset("vendor/blade-country-flags/4x3-{$siteLanguage['flag']}.svg") }}"
                    alt="{{ $siteLanguage['name'] }}"
                    loading="lazy"
                />
                {{ $siteLanguage['name'] }}
            </a>
        </div>
    @endforeach
</x-capell::dropdown>
