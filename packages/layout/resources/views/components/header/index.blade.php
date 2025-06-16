<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Services\Loader;
    use Capell\Layout\Actions\PageHasHeroWidgetAction;
@endphp

@props([
    'language' => Frontend::getLanguage(),
    'site' => Frontend::getSite(),
    'pageRecord' => Frontend::getPage(),
    'theme' => Frontend::getTheme(),
    'menuItemClass' => 'font-heading group flex w-full cursor-pointer items-center justify-between gap-x-2 px-6 py-3 text-sm font-semibold hover:bg-gray-50 focus-visible:bg-gray-50 lg:!bg-transparent lg:px-4 lg:py-7 lg:uppercase dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800',
])
@php
    $hasHero = PageHasHeroWidgetAction::run($pageRecord);

    $menu = Loader\NavigationLoader::getNavigation('main', $site, $language);
    if (! $menu) {
        $menu = Loader\NavigationLoader::getNavigation('main', $site);
    }

    $items = [];

    if ($menu) {
        $navigationLoader = new Loader\NavigationItemsLoader(
            navigation: $menu,
            site: $site,
            language: $language,
            siteDomain: $site->siteDomain
        );

        $items = $navigationLoader->fetchMenuItems();

        if ($items) {
            $navigationLoader->activeMenuItems($items);
        }
    }

    $pageLanguages = Loader\SiteLoader::pageLanguages($site, $language, $pageRecord);
@endphp

<header
    x-data="siteHeader"
    @class([
        'transition-padding fixed absolute sticky left-0 right-0 top-0 z-50 flex w-full border-b border-gray-100 bg-[rgb(var(--header-bg-color),var(--header-bg-opacity,1))] text-[rgb(var(--header-color))] transition-transform duration-300 ease-in-out lg:h-auto dark:border-gray-700 dark:bg-[rgb(var(--header-bg-dark-color),var(--header-bg-opacity,1))] dark:text-[rgb(var(--header-dark-color))]',
        'header-sticky' => $theme->sticky_header,
        'has-hero' => $hasHero,
        'left-0 right-0 top-0 z-50' => $hasHero || $theme->sticky_header,
        'fixed' => $hasHero && $theme->sticky_header,
        'sticky' => ! $hasHero && $theme->sticky_header,
        'absolute' => $hasHero && ! $theme->sticky_header,
    ])
    id="header"
    x-on:keydown.escape.prevent.stop="closeMenu()"
    :class="isMenuOpen || isClosingMenu ? 'h-screen' : ''"
>
    <div
        @class([
            'flex min-h-full w-full flex-col lg:relative lg:flex-row lg:items-center lg:gap-x-8',
            match ($theme->meta['container'] ?? null) {
                'sm', 'md', 'lg' => 'lg:container',
                default => 'container',
            },
        ])
    >
        <div
            @class([
                'relative flex w-full items-center justify-between px-6 py-[max(2vh,14px)] lg:static lg:w-auto lg:max-w-none lg:px-0 lg:py-0',
                match ($theme->meta['container'] ?? null) {
                    'sm' => 'sm:container',
                    'md' => 'md:container',
                    default => 'container',
                },
            ])
        >
            <div class="max-w-[250px] lg:order-1 lg:w-full xl:max-w-[350px]">
                <a
                    href="{{ $site->siteDomain->url }}"
                    aria-label="{{ __('capell-frontend::generic.home') }}"
                    wire:navigate
                >
                    @if ($site->logo)
                        <x-capell::logo
                            :invert="true"
                            class="header-logo hidden h-[12vh] max-h-[5rem] w-auto dark:block"
                        />
                        <x-capell::logo
                            class="header-logo h-[12vh] max-h-[5rem] w-auto dark:hidden"
                        />
                    @else
                        <span
                            class="header-logo-text header-color text-2xl font-bold tracking-tight"
                        >
                            {!! $site->translation->title !!}
                        </span>
                    @endif
                </a>
            </div>
            <div
                class="flex items-center justify-end gap-x-2 md:gap-x-1 lg:absolute lg:right-4 lg:top-1/2 lg:w-16 lg:translate-y-[-50%]"
            >
                <button
                    for="toggleMenu"
                    x-ref="toggleMenu"
                    class="toggleMenu header-color hover:text-primary focus:text-primary relative z-40 flex h-10 w-10 cursor-pointer items-center justify-center rounded-lg bg-gray-50 lg:hidden dark:bg-gray-600/75"
                    :title="isMenuOpen ? '{{ __('capell-frontend::generic.close_menu') }}' : '{{ __('capell-frontend::generic.open_menu') }}'"
                    :class="isMenuOpen ? 'bg-primary text-white hover:text-white hover:opacity-80 focus:text-white' : ''"
                    x-on:click="toggleMenu"
                >
                    <span
                        class="sr-only"
                        x-text="
                            isDarkMode
                                ? '{{ __('capell-frontend::generic.light_mode') }}'
                                : '{{ __('capell-frontend::generic.dark_mode') }}'
                        "
                    ></span>
                    @svg('heroicon-m-bars-3', 'h-6 w-6', ['x-show' => '!isMenuOpen'])
                    @svg('heroicon-o-x-mark', 'h-6 w-6', ['x-cloak' => '', 'x-show' => 'isMenuOpen'])
                </button>
            </div>
        </div>
        <div
            id="menu"
            class="menu-wrapper relative flex w-full grow justify-center bg-[var(--header-bg-color)] lg:visible lg:w-auto lg:!bg-transparent dark:bg-[var(--header-bg-dark-color)]"
            x-bind:class="isMenuOpen || isClosingMenu ? 'visible' : 'invisible'"
        >
            <div
                class="menu-backdrop absolute left-0 top-0 z-30 h-full w-full bg-black/75 invert-[.25] lg:hidden"
                x-on:click="closeMenu($refs.toggleMenu)"
            ></div>
            <nav
                id="navbar"
                class="navbar invisible absolute left-0 top-0 z-40 flex h-full w-full max-w-sm transform overflow-y-auto overflow-x-hidden border-t border-gray-100 bg-white transition-[translate,visibility] duration-500 ease-in-out lg:visible lg:static lg:max-w-none lg:translate-x-0 lg:overflow-visible lg:border-0 lg:bg-transparent lg:transition-none dark:border-gray-700 dark:bg-gray-900 dark:lg:bg-transparent"
                x-bind:class="isMenuOpen ? 'visible translate-x-0' : 'invisible translate-x-[-100%]'"
            >
                <ul
                    class="relative flex w-full flex-col justify-center pt-6 lg:static lg:flex-row lg:items-center lg:justify-end lg:space-x-2 lg:py-0"
                >
                    @foreach ($items as $id => $item)
                        @if (! empty($item['children']))
                            <x-capell::header.menu.dropdown
                                :id="$id"
                                :item="$item"
                                :items="$item['children']"
                                :menu="$menu"
                                :index="$loop->index"
                                :item-class="$menuItemClass"
                            />
                        @else
                            <x-capell::header.menu.item
                                :id="$id"
                                :item="$item"
                                :items="$item['children']"
                                :menu="$menu"
                                :index="$loop->index"
                                :item-class="$menuItemClass"
                            />
                        @endif
                    @endforeach

                    <li
                        class="mt-auto grid grid-cols-2 items-center justify-between divide-x divide-gray-100 border-t border-gray-100 pl-2 lg:mt-0 lg:flex lg:h-auto lg:divide-none lg:border-0 dark:divide-gray-700 dark:border-gray-700"
                    >
                        @if (count($pageLanguages) > 1)
                            <x-capell::header.menu.languages-dropdown
                                :language="$language"
                                :page-languages="$pageLanguages"
                                class="flex w-full justify-between pr-6 text-sm font-medium lg:w-auto lg:pr-0"
                            >
                                <x-slot:trigger>
                                    <button
                                        x-ref="button"
                                        x-on:click.stop="toggle()"
                                        :aria-expanded="open"
                                        :aria-controls="$id('dropdown-button')"
                                        type="button"
                                        class="focus:text-primary hover:text-primary flex w-full cursor-pointer items-center justify-between px-4 py-3 lg:w-auto lg:px-4 lg:py-7"
                                    >
                                        <span class="lg:hidden">
                                            {{ $language->name }}
                                        </span>
                                        <x-dynamic-component
                                            class="h-4 w-4 text-gray-400 group-hover:text-inherit group-focus:text-inherit"
                                            :component="'flag-4x3-'.$language->flag"
                                            :x-tooltip.raw="__('Change language')"
                                            :title="$language->name"
                                        />
                                    </button>
                                </x-slot>
                            </x-capell::header.menu.languages-dropdown>
                        @endif

                        <div>
                            <button
                                class="toggle-dark-mode hover:text-primary focus:text-primary flex w-full cursor-pointer justify-between px-6 py-3 text-sm font-medium lg:w-auto lg:px-4 lg:py-7"
                                x-on:click="toggleDarkMode"
                                x-tooltip="
                                    isDarkMode
                                        ? '{{ __('capell-frontend::generic.light_mode') }}'
                                        : '{{ __('capell-frontend::generic.dark_mode') }}'
                                "
                                tabindex="0"
                            >
                                <span
                                    class="lg:hidden"
                                    x-text="
                                        isDarkMode
                                            ? '{{ __('capell-frontend::generic.light_mode') }}'
                                            : '{{ __('capell-frontend::generic.dark_mode') }}'
                                    "
                                ></span>

                                <span class="ml-auto">
                                    @svg('heroicon-o-sun', 'hidden h-5 w-5 md:h-5 md:w-5 dark:block')
                                    @svg('heroicon-o-moon', 'h-4 w-4 stroke-current md:h-5 md:w-5 dark:hidden')
                                </span>
                            </button>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</header>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Alpine.data('siteHeader', () => ({
                isDarkMode: document.documentElement.classList.contains('dark'),
                isMenuOpen: false,
                isClosingMenu: false,
                init() {
                    this.$watch('isMenuOpen', (value) => {
                        this.isClosingMenu = true

                        setTimeout(() => {
                            this.isClosingMenu = false
                        }, 450)

                        document.body.classList.toggle('menu-open', value)
                    })

                    this.$watch('isDarkMode', (value) => {
                        document.documentElement.classList.toggle('dark', value)
                        localStorage.theme = value ? 'dark' : 'light'
                    })

                    window.addEventListener('close-menu', () => {
                        this.isMenuOpen = false
                    })
                },
                toggleDarkMode() {
                    this.isDarkMode = !this.isDarkMode
                },
                toggleMenu() {
                    if (this.isMenuOpen) {
                        return this.closeMenu()
                    }

                    return this.openMenu()
                },
                openMenu() {
                    if (this.isMenuOpen) return

                    this.$refs.toggleMenu.focus()
                    this.$refs.toggleMenu.setAttribute('aria-expanded', 'true')

                    this.isMenuOpen = true
                },
                closeMenu(focusAfter) {
                    if (!this.isMenuOpen) return

                    this.isMenuOpen = false

                    focusAfter && focusAfter.focus()
                },
            }))
        })
    </script>
@endpush

<?php
