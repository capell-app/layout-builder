<?php
use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();
$page = Frontend::page();
$layout = Frontend::layout();
$site = Frontend::site();
$isSystemPageLayout = data_get($layout->admin ?? [], 'system_page_layout') === true;

?>

@props([
    'containerClass' => null,
    'footer' => null,
    'header' => null,
    'mainClass' => null,
    'mainContainerClass' => null,
    'pageSlot' => null,
])
@if ($isSystemPageLayout)
    <div
        {{ $attributes->merge(['style' => 'min-height: 100vh; display: flex; flex-direction: column; background: #f8fafc; color: #0f172a;']) }}
    >
        <main
            id="main"
            style="
                box-sizing: border-box;
                width: 100%;
                max-width: 48rem;
                min-height: 100vh;
                margin: 0 auto;
                padding: 3rem 1.5rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
            "
        >
            <a
                href="{{ $site->defaultDomain?->url ?? $site->siteDomain?->url ?? '/' }}"
                style="
                    margin-bottom: 2.5rem;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    color: #0f172a;
                    font-size: 1.125rem;
                    font-weight: 600;
                    text-decoration: none;
                "
            >
                @if ($site->logo)
                    <x-capell::logo :media="$site->logo" />
                @else
                    <span>{{ $site->translation->title ?? $site->name }}</span>
                @endif
            </a>

            <x-capell::content
                :content="$page->translation->content"
                :content-type="$page->type->content_structure"
                :title="$page->translation->title"
                class="mx-auto max-w-2xl text-slate-700 [&_h1]:text-slate-950"
                heading-tag="h1"
                heading-size="h1"
                text-align="center"
            />

            {{ $pageSlot ?? $slot }}
        </main>
    </div>
@else
    <div
        {{ $attributes->merge(['class' => 'flex min-h-screen flex-col bg-white dark:bg-gray-900']) }}
    >
        <a
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:font-medium focus:text-gray-900 focus:shadow"
            href="#main"
        >
            {{ __('capell-frontend::generic.skip_link') }}
        </a>

        @if ($header)
            {{ $header }}
        @elseif ($header === null && (! isset($theme['meta']['header']) || $theme['meta']['header'] !== false))
            @if (! empty($theme['meta']['header_file']))
                <x-dynamic-component
                    :component="$theme['meta']['header_file']"
                />
            @else
                <x-capell::header.index />
            @endif
        @endif

        <x-capell::layout.main
            :$layout
            :$page
            :$theme
            :page-slot="$pageSlot ?? $slot"
            :container-class="$containerClass"
            :main-class="$mainClass"
            :main-container-class="$mainContainerClass"
        />

        @if ($footer)
            {{ $footer }}
        @elseif ($footer === null && (! isset($theme['meta']['footer']) || $theme['meta']['footer'] !== false))
            <x-dynamic-component
                :component="$theme['meta']['footer_file'] ?? 'capell::footer'"
            />
        @endif
    </div>
@endif
