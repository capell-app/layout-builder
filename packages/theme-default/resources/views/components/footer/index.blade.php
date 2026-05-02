@php
    use Capell\Core\Actions\ColorConverterAction;
    use Capell\Core\Models\Language;
    use Capell\Core\Models\Page;
    use Capell\Frontend\Actions\GetLayoutContainerWidthAction;
    use Capell\Frontend\Enums\RenderHookLocation;
    use Capell\Frontend\Facades\Frontend;
    use Capell\Frontend\Support\Loader\SiteLoader;
    use Capell\Frontend\Support\Render\RenderHookRegistry;
    use Capell\Navigation\Enums\NavigationHandle;
    use Capell\Navigation\Models\Navigation;
    use Capell\Navigation\Support\Loader\NavigationItemsLoader;
    use Capell\Navigation\Support\Loader\NavigationLoader;

    $language = Frontend::language();
    $site = Frontend::site();
    $page = Frontend::page();
    $theme = Frontend::theme();
    $layout = Frontend::layout();

    $getMenu = function (string $key, ?Language $language) use ($page, $site): array {
        $menu = NavigationLoader::getNavigation($key, $site, $language);

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

        return [$menu, $items];
    };

    [$footerMenu, $footerMenuItems] = $getMenu(NavigationHandle::Footer->value, $language);
    [$subFooterMenu, $subFooterMenuItems] = $getMenu(NavigationHandle::SubFooter->value, $language);

    $contactPage = Page::getFirstPageByTypeForSite('contact', $site, $language);

    $siteLanguages = SiteLoader::pageLanguages($site, $language, $page);

    $footerCopy = $site->translation->getMeta('footer_copy');

    $containerWidth = GetLayoutContainerWidthAction::run();
@endphp

@props([
    'headingClass' => 'font-heading text-lg font-light leading-tight text-gray-300',
])
<style>
    :root {
        --color-footer: {{ ColorConverterAction::run($theme->getMeta('footer_color', '245,245,245')) }};
        --bg-color-footer: {{ ColorConverterAction::run($theme->getMeta('footer_background_color', '69,69,72')) }};
    }

    .dark:root {
        --color-footer: {{ ColorConverterAction::run($theme->getMeta('footer_dark_color', '233,233,233')) }};
        --bg-color-footer: {{ ColorConverterAction::run($theme->getMeta('footer_dark_background_color', '32,31,40')) }};
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
    class="z-0 bg-[var(--bg-color-footer)] text-sm text-[var(--color-footer)]"
>
    <div
        @class([
            '@container flex-wrap px-8 py-14 lg:pt-16',
            $containerWidth->getContainerClass(),
        ])
    >
        <div
            class="@2xl:grid-cols-2 @4xl:grid-cols-3 grid gap-x-8 gap-y-10 xl:flex xl:flex-row xl:gap-x-10"
        >
            <x-capell::footer.site-info
                :$site
                class="order-2 shrink-0 text-center lg:order-1 lg:text-left xl:max-w-[30%] xl:pr-10"
            />

            <div
                class="@4xl:col-span-2 order-1 grid grow gap-10 lg:order-2 xl:flex"
            >
                @if ($footerMenuItems?->isNotEmpty())
                    <x-capell::footer.menu
                        :$headingClass
                        :items="$footerMenuItems"
                        class="grow"
                    />
                @endif

                {!!
                    app(RenderHookRegistry::class)->renderAll(
                        RenderHookLocation::Footer,
                        item: ['headingClass' => $headingClass],
                        target: 'footer.index',
                    )
                !!}
            </div>
        </div>
    </div>

    @if ($subFooterMenuItems?->isNotEmpty() || $footerCopy || count($siteLanguages) > 1)
        <x-capell::footer.sub-footer
            :items="$subFooterMenuItems"
            :$siteLanguages
            class="sub-footer border-t border-white/5"
        >
            {{
                Lang::get($footerCopy, [
                    'name' => $site->name,
                    'year' => date('Y'),
                ])
            }}
        </x-capell::footer.sub-footer>
    @endif
</footer>

@include('capell::partials.svg-sprite')
