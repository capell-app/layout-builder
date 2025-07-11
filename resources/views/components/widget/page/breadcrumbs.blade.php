<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Actions\ReplacePageDataAction;
    use Capell\Frontend\Facades\Frontend;

    $page = Frontend::getPage();
    $pageParams = Frontend::getPageParams();
    $site = Frontend::getSite();
@endphp

@props([
    'container',
    'containerKey',
    'containerWidth' => null,
    'loop',
    'widget',
])
@php
    $currentPageLabel = ReplacePageDataAction::run($page->translation->label, $pageParams);
@endphp

<nav
    class="widget-breadcrumbs my-4 text-gray-800"
    aria-label="{{ __('capell-frontend::generic.breadcrumbs') }}"
>
    <x-capell-layout::widget.wrapper
        :$container
        :$containerKey
        :$containerWidth
        :index="$loop->index"
        :margin="[]"
        :$widget
        container-class="flex"
    >
        <ol class="inline-flex flex-wrap items-center space-x-1 md:space-x-2">
            <li class="inline-flex items-center">
                <a
                    class="hover:text-primary focus:text-primary inline-flex items-center text-sm font-medium text-gray-400"
                    href="{{ $site->siteDomain->url }}"
                    wire:navigate
                >
                    @svg('heroicon-m-home', 'h-4 w-4 fill-current')
                    <span class="sr-only">
                        {{ __('capell-frontend::generic.home') }}
                    </span>
                </a>
            </li>
            @foreach ($pages as $item)
                <li>
                    <div class="flex items-center">
                        @svg('heroicon-m-chevron-right', 'mr-1 h-4 w-4 text-gray-400')
                        <a
                            class="hover:text-primary focus:text-primary text-gray line-clamp-1 text-sm font-medium dark:text-gray-400"
                            href="{{ $item->pageUrl->full_url }}"
                            title="{{ htmlspecialchars(strip_tags($item->translation->label)) }}"
                            wire:navigate
                        >
                            {{ str($item->translation->label)->limit(30) }}
                        </a>
                    </div>
                </li>
            @endforeach

            <li aria-current="page">
                <div class="flex items-center">
                    @svg('heroicon-m-chevron-right', 'mr-1 h-4 w-4 text-gray-400')
                    <span
                        class="text-sm font-light text-gray-500"
                        title="{{ htmlspecialchars(strip_tags($currentPageLabel)) }}"
                    >
                        {{ $currentPageLabel }}
                    </span>
                </div>
            </li>
        </ol>
    </x-capell-layout::widget.wrapper>
</nav>

<?php
