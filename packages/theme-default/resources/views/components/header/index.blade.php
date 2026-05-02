@php
    use Capell\Core\Actions\ColorConverterAction;
    use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
    use Capell\Frontend\Enums\RenderHookLocation;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\Render\RenderHookRegistry;
    use Capell\Navigation\Enums\NavigationHandle;
    use Capell\Navigation\Models\Navigation;
    use Capell\Navigation\Support\Loader\NavigationItemsLoader;
    use Capell\Navigation\Support\Loader\NavigationLoader;

    $language = Frontend::language();
    $site = Frontend::site();
    $page = Frontend::page();
    $theme = Frontend::theme();

    $menu = NavigationLoader::getNavigation(NavigationHandle::Main->value, $site, $language);
    if (! $menu instanceof Navigation) {
        $menu = NavigationLoader::getNavigation(NavigationHandle::Main->value, $site);
    }

    $items = null;

    if ($menu instanceof Navigation) {
        $navigationLoader = new NavigationItemsLoader(
            navigation: $menu,
            page: $page,
            site: $site,
            language: $language,
            siteDomain: $site->siteDomain,
        );

        $items = $navigationLoader->fetchMenuItems();

        if ($items->isNotEmpty()) {
            $navigationLoader->activeMenuItems($items);
        }
    }

    $headerBorderColor = $theme->getMeta('header_border_color');

    $containerWidth = GetLayoutContainerWidthAction::run();
@endphp

@props([
    'menuItemClass' => 'nav-item font-heading group flex w-full cursor-pointer items-center justify-between gap-x-2 px-6 py-3 text-sm font-medium hover:bg-gray-50 focus-visible:bg-gray-50 lg:!bg-transparent lg:px-4 lg:py-1 dark:hover:bg-gray-800 dark:focus-visible:bg-gray-800',
])

<style>
    :root {
        --header-height: {{ $theme->getMeta('header_height', '4.7rem') }};
        --color-header: {{ ColorConverterAction::run($theme->getMeta('header_color', '32,31,40')) }};
        --bg-color-header: {{ ColorConverterAction::run($theme->getMeta('header_background_color', '255,255,255')) }};
        --bg-color-main: {{ ColorConverterAction::run($theme->getMeta('main_background_color', '247,248,249')) }};
        --border-header: {{ $headerBorderColor ? ColorConverterAction::run($headerBorderColor) : 'transparent' }};
    }

    .dark:root {
        --color-header: {{ ColorConverterAction::run($theme->getMeta('header_dark_color', '233,233,233')) }};
        --bg-color-header: {{ ColorConverterAction::run($theme->getMeta('header_dark_background_color', '32,31,40')) }};
        --bg-color-main: {{ ColorConverterAction::run($theme->getMeta('main_dark_background_color', '32,31,40')) }};
    }

    #header.has-hero:not(.header-sticky):has(.fixed, .sticky) {
        --header-bg-opacity: 0.8;
    }
</style>

{!! app(RenderHookRegistry::class)->renderAll(RenderHookLocation::HeaderBefore) !!}

<header
    x-data="siteHeader({ scrollUp: {{ $theme->scroll_up_header ? 'true' : 'false' }} })"
    @class([
        'transition-padding left-0 right-0 top-0 z-50 flex min-h-[var(--header-height)] w-full bg-[var(--bg-color-header)] text-[var(--color-header)] transition-transform duration-300 ease-in-out max-lg:bg-transparent lg:h-auto',
        'border-b border-[var(--border-header)] dark:border-b-0' => $headerBorderColor,
        'header-sticky sticky left-0 right-0 top-0 z-50' => $theme->sticky_header,
        'header-fixed fixed left-0 right-0 top-0 z-50' => $theme->fixed_header,
        'header-scroll-up fixed left-0 right-0 top-0 z-50' => $theme->scroll_up_header,
    ])
    id="header"
    x-on:keydown.escape.prevent.stop="closeMenu()"
    :class="{
        'h-screen': isMenuOpen || isClosingMenu,
        '-translate-y-full': scrollUp && isHidden && !isMenuOpen,
    }"
>
    <div
        @class([
            'w-full max-lg:px-0 lg:relative lg:flex lg:items-center lg:gap-x-10 2xl:gap-x-20',
            $containerWidth->getContainerClass(),
        ])
    >
        <div
            @class([
                'relative flex items-center justify-between bg-[var(--bg-color-header)] py-[max(2vh,14px)] lg:static lg:!w-auto lg:!max-w-none lg:!px-0 lg:py-0',
                $containerWidth->getContainerClass(),
            ])
        >
            <div
                class="max-w-[250px] lg:order-1 lg:w-full lg:py-2 xl:max-w-[350px]"
            >
                <a
                    href="{{ $site->siteDomain->url }}"
                    aria-label="{{ __('capell-frontend::generic.home') }}"
                    wire:navigate
                    class="text-brand hover:text-primary focus:text-primary"
                >
                    @if ($site->logo || $site->logoInverted)
                        @if ($site->logoInverted)
                            <x-capell::logo
                                :media="$site->logoInverted"
                                :class="'header-logo h-[12vh] max-h-[5rem] w-auto' . ($site->logo ? ' hidden dark:block' : '')"
                            />
                        @endif

                        @if ($site->logo)
                            <x-capell::logo
                                :media="$site->logo"
                                :class="'header-logo h-[12vh] max-h-[5rem] w-auto' . ($site->logoInverted ? ' dark:hidden' : '')"
                            />
                        @endif
                    @else
                        <span
                            class="header-logo-text whitespace-nowrap text-lg font-bold tracking-tight xl:text-xl"
                        >
                            {{ $site->translation->title }}
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
                    class="toggleMenu color-header hover:text-primary focus:text-primary relative z-40 flex h-10 w-10 cursor-pointer items-center justify-center rounded-lg bg-gray-50 lg:hidden dark:bg-gray-600/75"
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
        @if ($items?->isNotEmpty())
            <div
                id="menu"
                class="menu-wrapper max-lg:height-full relative flex h-full w-full grow justify-center bg-[var(--bg-color-header)] max-lg:bg-transparent lg:visible lg:w-auto lg:!bg-transparent"
                x-bind:class="isMenuOpen || isClosingMenu ? 'visible' : 'invisible'"
            >
                <div
                    class="menu-backdrop absolute left-0 top-0 z-30 h-full w-full bg-black/75 invert-[.25] lg:hidden"
                    x-on:click="closeMenu($refs.toggleMenu)"
                ></div>
                <nav
                    id="main-menu"
                    class="navbar invisible absolute left-0 top-0 z-40 flex h-full w-full max-w-md transform flex-col overflow-y-auto overflow-x-hidden border-t border-gray-100 bg-white transition-[translate,visibility] duration-500 ease-in-out lg:visible lg:static lg:max-w-none lg:translate-x-0 lg:flex-row lg:overflow-visible lg:border-0 lg:bg-transparent lg:transition-none dark:border-gray-700 dark:bg-gray-950 dark:lg:bg-transparent"
                    x-bind:class="isMenuOpen ? 'visible translate-x-0' : 'invisible translate-x-[-100%]'"
                >
                    <ul
                        @class([
                            'nav-items relative flex w-full flex-col flex-wrap justify-center gap-y-0.5 pt-6 lg:static lg:flex-row lg:space-x-2 lg:py-3',
                            'lg:justify-start' => $theme->getMeta('header_menu_alignment') === 'left',
                            'lg:justify-center' => $theme->getMeta('header_menu_alignment') === 'center',
                            'lg:justify-end' => $theme->getMeta('header_menu_alignment') === 'right',
                        ])
                    >
                        @foreach ($items as $id => $item)
                            @if ($item->children->count() > 0)
                                <x-capell::header.menu.dropdown
                                    :id="$id"
                                    :item="$item"
                                    :menu="$menu"
                                    :index="$loop->index"
                                    :item-class="$menuItemClass"
                                />
                            @else
                                <x-capell::header.menu.item
                                    :id="$id"
                                    :item="$item"
                                    :menu="$menu"
                                    :index="$loop->index"
                                    :item-class="$menuItemClass"
                                />
                            @endif
                        @endforeach
                    </ul>
                    @if ($theme->getMeta('dark_mode_toggle'))
                        <div
                            class="grid grid-cols-2 items-center justify-between gap-x-2 border-t border-gray-100 p-4 lg:mt-0 lg:flex lg:h-auto lg:gap-x-3 lg:divide-none lg:border-0 lg:px-0 lg:py-2 dark:border-gray-700"
                        >
                            <button
                                class="hover:text-primary flex h-auto w-full cursor-pointer justify-between rounded-lg border border-gray-100 px-3 py-3 lg:w-auto dark:border-gray-600"
                                x-on:click="toggleDarkMode"
                                x-tooltip="
                                    isDarkMode
                                        ? '{{ __('capell-frontend::generic.dark_mode') }}'
                                        : '{{ __('capell-frontend::generic.light_mode') }}'
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
                                    @svg('heroicon-o-sun', 'hidden h-4 w-4 md:h-5 md:w-5 dark:block')
                                    @svg('heroicon-o-moon', 'h-4 w-4 stroke-current md:h-5 md:w-5 dark:hidden')
                                </span>
                            </button>
                        </div>
                    @endif
                </nav>
            </div>
        @endif

        <div class="hidden shrink-0 items-center py-3 lg:flex">
            {!! app(RenderHookRegistry::class)->renderAll(RenderHookLocation::HeaderAfter) !!}
        </div>
    </div>
</header>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Alpine.data('siteHeader', ({ scrollUp = false } = {}) => ({
                isDarkMode: document.documentElement.classList.contains('dark'),
                isMenuOpen: false,
                isClosingMenu: false,
                scrollUp,
                isHidden: false,
                lastScrollY: 0,
                init() {
                    if (this.scrollUp) {
                        this.lastScrollY = window.scrollY
                        window.addEventListener(
                            'scroll',
                            () => {
                                const currentY = window.scrollY
                                const delta = currentY - this.lastScrollY
                                if (currentY <= 0) {
                                    this.isHidden = false
                                } else if (delta > 4) {
                                    this.isHidden = true
                                } else if (delta < -4) {
                                    this.isHidden = false
                                }
                                this.lastScrollY = currentY
                            },
                            { passive: true },
                        )
                    }

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
