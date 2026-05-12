@php
    use Capell\Core\Actions\ColorConverterAction;
    use Capell\Core\Models\Language;
    use Capell\Core\Models\Page;
    use Capell\FoundationTheme\Support\NavigationAvailability;
    use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
    use Capell\Frontend\Actions\RenderHtmlContentAction;
    use Capell\Frontend\Enums\RenderHookLocation;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\Loader\SiteLoader;
    use Capell\Frontend\Support\Render\RenderHookRegistry;
    use Capell\Navigation\Actions\BuildNavigationRenderModelAction;
    use Capell\Navigation\Data\NavigationRenderContextData;
    use Capell\Navigation\Enums\NavigationHandle;
    use Capell\Navigation\Models\Navigation;
    use Capell\Navigation\Support\Loader\NavigationLoader;

    $language = Frontend::language();
    $site = Frontend::site();
    $page = Frontend::page();
    $theme = Frontend::theme();
    $layout = Frontend::layout();

    $navigationAvailable = NavigationAvailability::check();

    $getMenu = function (string $key, ?Language $language) use ($navigationAvailable, $page, $site): array {
        if (! $navigationAvailable) {
            return [null, null];
        }

        $menu = NavigationLoader::getNavigation($key, $site, $language);

        $items = null;

        if ($menu instanceof Navigation && $language instanceof Language) {
            $navigationRenderData = BuildNavigationRenderModelAction::run(new NavigationRenderContextData(
                navigation: $menu,
                page: $page,
                site: $site,
                language: $language,
                siteDomain: $site->siteDomain,
            ));

            $items = $navigationRenderData->items;
        }

        return [$menu, $items];
    };

    [$footerMenu, $footerMenuItems] = $navigationAvailable
        ? $getMenu(NavigationHandle::Footer->value, $language)
        : [null, null];
    [$subFooterMenu, $subFooterMenuItems] = $navigationAvailable
        ? $getMenu(NavigationHandle::SubFooter->value, $language)
        : [null, null];

    $contactPage = Page::getFirstPageByTypeForSite('contact', $site, $language);

    $siteLanguages = SiteLoader::pageLanguages($site, $language, $page);

    $footerCopy = $site->translation->getMeta('footer_copy');

    $containerWidth = GetLayoutContainerWidthAction::run();
    $footerSpacing = $theme->getMeta('footer_spacing', 'compact');
    $footerDividerColor = $theme->getMeta('footer_divider') ? $theme->getMeta('footer_border_color') : null;
@endphp

@props([
    'headingClass' => 'font-heading text-sm font-semibold uppercase leading-tight tracking-[0.08em] text-[var(--color-footer-heading)]',
])
@php
    $footerRenderHooks = app(RenderHookRegistry::class)->renderAll(
        RenderHookLocation::Footer,
        item: ['headingClass' => $headingClass],
        target: 'footer.index',
    );
    $hasFooterMenu = $footerMenuItems?->isNotEmpty() === true;
    $hasFooterRenderHooks = trim((string) $footerRenderHooks) !== '';
    $hasFooterPrimaryContent = $hasFooterMenu || $hasFooterRenderHooks;
@endphp

<style>
    :root {
        --color-footer: {{ ColorConverterAction::run($theme->getMeta('footer_color', '#1f2937')) }};
        --color-footer-heading: color-mix(
            in srgb,
            var(--color-footer),
            #020617 18%
        );
        --color-footer-muted: color-mix(
            in srgb,
            var(--color-footer),
            var(--bg-color-footer) 28%
        );
        --color-footer-link: color-mix(
            in srgb,
            var(--color-footer),
            #020617 8%
        );
        --bg-color-footer: {{ ColorConverterAction::run($theme->getMeta('footer_background_color', '#f1f5f9')) }};
        --bg-color-footer-panel: color-mix(
            in srgb,
            var(--bg-color-footer),
            #020617 3%
        );
        --bg-color-footer-muted: color-mix(
            in srgb,
            var(--bg-color-footer),
            #020617 6%
        );
        --border-color-footer: {{ ColorConverterAction::run($theme->getMeta('footer_border_color', '#e2e8f0')) }};
    }

    .dark:root {
        --color-footer: {{ ColorConverterAction::run($theme->getMeta('footer_dark_color', '#e5e7eb')) }};
        --color-footer-heading: color-mix(
            in srgb,
            var(--color-footer),
            #ffffff 10%
        );
        --color-footer-muted: color-mix(
            in srgb,
            var(--color-footer),
            var(--bg-color-footer) 24%
        );
        --color-footer-link: color-mix(
            in srgb,
            var(--color-footer),
            #ffffff 6%
        );
        --bg-color-footer: {{ ColorConverterAction::run($theme->getMeta('footer_dark_background_color', '#111827')) }};
        --bg-color-footer-panel: color-mix(
            in srgb,
            var(--bg-color-footer),
            #ffffff 5%
        );
        --bg-color-footer-muted: color-mix(
            in srgb,
            var(--bg-color-footer),
            #ffffff 8%
        );
        --border-color-footer: {{ ColorConverterAction::run($theme->getMeta('footer_dark_border_color', '#374151')) }};
    }
</style>

<a
    href="javascript:void(0)"
    class="scroll-top hover:bg-primary focus:bg-primary text-primary z-999 sticky bottom-0 left-full hidden h-10 w-10 -translate-x-6 items-center justify-center rounded-t-sm bg-gray-200 transition hover:text-white focus:text-white"
    title="{{ __('Scroll to top') }}"
>
    @svg('heroicon-o-chevron-up', 'h-6 w-6')
</a>
<footer
    id="footer"
    @class([
        'z-0 bg-[var(--bg-color-footer)] text-sm text-[var(--color-footer)]',
        'border-t border-[var(--border-color-footer)]' => $footerDividerColor,
    ])
>
    <div
        @class([
            '@container flex-wrap px-8',
            'py-6 lg:py-7' => $footerSpacing === 'compact',
            'py-8 lg:py-10' => $footerSpacing === 'default' && ! $hasFooterPrimaryContent,
            'py-10 lg:py-12' => $footerSpacing === 'default' && $hasFooterPrimaryContent,
            'py-12 lg:py-14' => $footerSpacing === 'comfortable' && ! $hasFooterPrimaryContent,
            'py-14 lg:py-16' => $footerSpacing === 'comfortable' && $hasFooterPrimaryContent,
            $containerWidth->getContainerClass(),
        ])
    >
        <div
            @class([
                'px-0 py-0',
                'flex justify-center' => ! $hasFooterPrimaryContent,
                '@2xl:grid-cols-2 @4xl:grid-cols-3 grid gap-x-8 gap-y-8 xl:flex xl:flex-row xl:gap-x-10' => $hasFooterPrimaryContent,
            ])
        >
            <x-capell::footer.site-info
                :$site
                @class([
                    'shrink-0',
                    'max-w-xl text-center' => ! $hasFooterPrimaryContent,
                    'order-2 text-center lg:order-1 lg:text-left xl:max-w-[30%] xl:pr-10' => $hasFooterPrimaryContent,
                ])
            />

            @if ($hasFooterPrimaryContent)
                <div
                    class="@4xl:col-span-2 order-1 grid grow gap-8 lg:order-2 xl:flex"
                >
                    @if ($hasFooterMenu)
                        <x-capell::footer.menu
                            :$headingClass
                            :items="$footerMenuItems"
                            class="grow"
                        />
                    @endif

                    {!! $footerRenderHooks !!}
                </div>
            @endif
        </div>
    </div>

    @if ($subFooterMenuItems?->isNotEmpty() || $footerCopy || count($siteLanguages) > 1)
        <div class="bg-[var(--bg-color-footer-muted)]">
            <x-capell::footer.sub-footer
                :items="$subFooterMenuItems"
                :$siteLanguages
                class="sub-footer"
            >
                {!!
                    RenderHtmlContentAction::run(Lang::get($footerCopy, [
                        'name' => $site->name,
                        'year' => date('Y'),
                    ]))
                !!}
            </x-capell::footer.sub-footer>
        </div>
    @endif
</footer>

@include('capell::partials.svg-sprite')
