<?php

declare(strict_types=1);

use Capell\Blog\Enums\BlogTypeGroupEnum;

$language = \Capell\Frontend\Facades\Frontend::language();
$site = \Capell\Frontend\Facades\Frontend::site();
$page = \Capell\Frontend\Facades\Frontend::page();

$getMenu = function (string $key) use ($site, $language, $page): array {
    $menu = \Capell\Frontend\Services\Loader\NavigationLoader::getNavigation($key, $site, $language);
    if (! $menu instanceof \Capell\Core\Models\Navigation) {
        $menu = \Capell\Frontend\Services\Loader\NavigationLoader::getNavigation($key, $site);
    }

    $items = [];

    if ($menu instanceof \Capell\Core\Models\Navigation) {
        $navigationLoader = new \Capell\Frontend\Services\Loader\NavigationItemsLoader(
            navigation: $menu,
            page: $page,
            site: $site,
            language: $language,
            siteDomain: $site->siteDomain,
        );

        $items = $navigationLoader->fetchMenuItems();

        if ($items !== []) {
            $navigationLoader->activeMenuItems($items);
        }
    }

    return [$menu, $items];
};

[$footerMenu, $footerMenuItems] = $getMenu(\Capell\Core\Enums\NavigationHandle::Footer->value);
[$subFooterMenu, $subFooterMenuItems] = $getMenu(\Capell\Core\Enums\NavigationHandle::SubFooter->value);

$contactPage = \Capell\Core\Models\Page::getFirstPageByTypeForSite('contact', $site, $language);

$siteLanguages = \Capell\Frontend\Services\Loader\SiteLoader::pageLanguages($site, $language, $page);

$pages = \Capell\Frontend\Services\Loader\PageLoader::getPages(
    language: $language,
    site: $site,
    limit: 3,
    ordering: 'latest',
    pageGroup: BlogTypeGroupEnum::Article,
    withImage: true,
);

?>

@props([
'menuItemClass' => 'hover:text-primary focus:text-primary flex gap-x-0.5 break-all font-semibold leading-tight text-white dark:text-gray-200',
'menuSubItemClass' => 'hover:text-primary focus:text-primary flex gap-x-0.5 break-all py-1 text-sm leading-tight text-white dark:text-gray-200',
'headingClass' => 'font-heading tracking-right font-medium uppercase leading-tight text-gray-400',
])
<style>
    :root {
        --color-footer: {{ \Capell\Core\Actions\ColorConverterAction::run($theme->meta['footer_color'] ?? '245,245,245') }};
        --bg-color-footer: {{ \Capell\Core\Actions\ColorConverterAction::run($theme->meta['footer_background_color'] ?? '69,69,72') }};
    }

    .dark:root {
        --color-footer: {{ \Capell\Core\Actions\ColorConverterAction::run($theme->meta['footer_dark_color'] ?? '233,233,233') }};
        --bg-color-footer: {{ \Capell\Core\Actions\ColorConverterAction::run($theme->meta['footer_dark_background_color'] ?? '32,31,40') }};
    }
</style>

<a
    href="javascript:void(0)"
    class="scroll-top hover:bg-primary focus:bg-primary text-primary z-999 sticky bottom-0 left-full hidden h-10 w-10 -translate-x-6 items-center justify-center rounded-t-sm bg-gray-200 transition hover:text-white focus:text-white dark:bg-gray-600/75"
    title="{{ __('Scroll to top') }}"
>
    @svg('heroicon-o-chevron-up', 'h-6 w-6')
</a>
<footer
    id="footer"
    class="z-0 bg-[var(--bg-color-footer)] text-[var(--color-footer)]"
>
    <div
        @class([
        '@container flex-wrap px-8 py-14 lg:pt-16',
        match ($theme->meta['container'] ?? null) {
        'sm' => 'sm:container',
        'md' => 'md:container',
        'lg' => 'lg:container',
        default => 'container',
        },
        ])
    >
        <div
            class="@2xl:grid-cols-2 @4xl:grid-cols-3 grid gap-x-8 gap-y-10 xl:flex xl:flex-row xl:gap-x-10"
        >
            <div
                class="shrink-0 space-y-6 text-center md:text-left xl:max-w-[30%]"
            >
                <a
                    href="{{ $site->siteDomain->url }}"
                    class="mb-6 inline-block"
                >
                    @if ($site->logo || $site->logoInverted)
                        @if ($site->logoInverted)
                            <x-capell::logo
                                :media="$site->logoInverted"
                                :class="'footer-logo object-top-left max-h-[32vh] object-contain' . ($site->logo ? ' hidden dark:block' : '')"
                            />
                        @endif

                        @if ($site->logo)
                            <x-capell::logo
                                :media="$site->logo"
                                :class="'footer-logo object-top-left max-h-[32vh] object-contain' . ($site->logoInverted ? ' dark:hidden' : '')"
                            />
                        @endif
                    @else
                        <span
                            class="footer-logo-text text-2xl font-semibold leading-tight text-gray-300 dark:text-gray-100"
                        >
                            {!! $site->translation->title !!}
                        </span>
                    @endif
                </a>
                @if (! empty($site->translation->meta['tagline']))
                    <p
                        class="footer-tagline text-sm font-medium dark:text-gray-400"
                    >
                        {{ $site->translation->meta['tagline'] }}
                    </p>
                @endif

                @if (count($siteLanguages) > 1)
                    <x-capell::languages
                        :$siteLanguages
                        class="mx-auto"
                    />
                @endif
            </div>

            @if ($footerMenuItems)
                <x-capell::footer.menu
                    :$headingClass
                    :$menuItemClass
                    :$menuSubItemClass
                    :items="$footerMenuItems"
                    class="@4xl:col-span-2 g:pl-6 shrink-0 grow lg:pt-2"
                />
            @endif

            <x-capell-layout::footer.pages
                :$headingClass
                :$pages
                class="shrink-0 xl:w-[20%]"
            />

            <x-capell::footer.contact
                :$headingClass
                class="@4xl:col-span-2 shrink-0 xl:w-[20%]"
            />

            {!! app(\Capell\Frontend\Services\RenderHookRegistry::class)->renderAll(\Capell\Frontend\Enums\RenderHookLocation::Footer) !!}
        </div>
    </div>

    @if (! empty($subFooterMenuItems) || ! empty($site->translation->meta['footer_copy']))
        <x-capell::footer.sub-footer
            :items="$subFooterMenuItems"
            class="sub-footer border-t border-white/10"
        >
            {!!
                \Illuminate\Support\Facades\Lang::get($site->translation->meta['footer_copy'] ?? '', [
                'name' => $site->name,
                'year' => date('Y'),
                ])
            !!}
        </x-capell::footer.sub-footer>
    @endif
</footer>

@include('capell::partials.svg-sprite')

<?php
