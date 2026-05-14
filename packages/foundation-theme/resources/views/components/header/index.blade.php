@php
    use Capell\Core\Actions\ColorConverterAction;
    use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
    use Capell\Frontend\Enums\RenderHookLocation;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\Render\RenderHookRegistry;

    $site = Frontend::site();
    $theme = Frontend::theme();
    $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    $usesAlpine = $runtimeManifest?->usesAlpine ?? false;
    $usesWireNavigate = $runtimeManifest?->usesWireNavigate ?? false;

    $headerBorderColor = $theme->getMeta('header_divider') ? $theme->getMeta('header_border_color') : null;
    $headerDarkBorderColor = $theme->getMeta('header_divider') ? $theme->getMeta('header_dark_border_color', $headerBorderColor) : null;
    $headerShadow = $theme->getMeta('header_shadow', 'none');

    $containerWidth = GetLayoutContainerWidthAction::run();
@endphp

@props([
    'menuItemClass' => 'capell-product-nav-item nav-item font-heading group cursor-pointer',
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
        --border-header: {{ $headerDarkBorderColor ? ColorConverterAction::run($headerDarkBorderColor) : 'transparent' }};
    }

    #header.has-hero:not(.header-sticky):has(.fixed, .sticky) {
        --header-bg-opacity: 0.8;
    }
</style>

{!! app(RenderHookRegistry::class)->renderAll(RenderHookLocation::HeaderBefore) !!}

<header
    @if ($usesAlpine) x-data="siteHeader({ scrollUp: {{ $theme->scroll_up_header ? 'true' : 'false' }} })" @endif
    @class([
        'capell-product-header transition-padding left-0 right-0 top-0 z-50 flex min-h-[var(--header-height)] w-full text-[var(--color-header)] transition-transform duration-300 ease-in-out lg:h-auto',
        'border-b border-[var(--border-header)]' => $headerBorderColor,
        'shadow-sm shadow-black/5 dark:shadow-black/20' => $headerShadow === 'subtle',
        'header-sticky sticky left-0 right-0 top-0 z-50' => $theme->sticky_header,
        'header-fixed fixed left-0 right-0 top-0 z-50' => $theme->fixed_header,
        'header-scroll-up fixed left-0 right-0 top-0 z-50' => $theme->scroll_up_header,
    ])
    id="header"
    @if ($usesAlpine)
        :class="{
                                                                    'h-screen': isNavigationOverlayOpen,
                                                                    '-translate-y-full': scrollUp && isHidden && !isNavigationOverlayOpen,
                                                                }"
    @endif
>
    <div
        @class([
            'capell-product-header__inner relative w-full max-lg:px-0',
            $containerWidth->getContainerClass(),
        ])
    >
        <div
            @class([
                'capell-product-header__brand relative',
            ])
        >
            <div
                class="min-w-0 max-w-[250px] lg:order-1 lg:w-full xl:max-w-[350px]"
            >
                <a
                    href="{{ $site->siteDomain->url }}"
                    aria-label="{{ __('capell-frontend::generic.home') }}"
                    @if ($usesWireNavigate) @wireNavigate @endif
                    class="capell-product-header__brand-link text-brand hover:text-primary focus:text-primary"
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
                            class="capell-product-header__logo-text header-logo-text"
                        >
                            {{ $site->translation->title }}
                        </span>
                    @endif
                </a>
            </div>
        </div>
        {!!
            app(RenderHookRegistry::class)->renderAll(
                RenderHookLocation::HeaderAfter,
                ['menuItemClass' => $menuItemClass],
                scenario: 'foundation-theme-primary-navigation',
                target: 'capell::header.index',
            )
        !!}
    </div>
</header>

@if ($usesAlpine)
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('siteHeader', ({ scrollUp = false } = {}) => ({
                isDarkMode: document.documentElement.classList.contains('dark'),
                isNavigationOverlayOpen: false,
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

                    this.$watch('isDarkMode', (value) => {
                        document.documentElement.classList.toggle('dark', value)
                        localStorage.theme = value ? 'dark' : 'light'
                    })

                    window.addEventListener(
                        'capell-navigation-menu-open-changed',
                        (event) => {
                            this.isNavigationOverlayOpen = Boolean(
                                event.detail?.open,
                            )
                        },
                    )
                },
                toggleDarkMode() {
                    this.isDarkMode = !this.isDarkMode
                },
            }))
        })
    </script>
@endif
