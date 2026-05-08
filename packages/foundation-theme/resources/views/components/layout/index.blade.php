<?php
use Capell\Frontend\Facades\Frontend;

$theme = Frontend::theme();
$page = Frontend::page();
$layout = Frontend::layout();

?>

@props([
    'containerClass' => null,
    'footer' => null,
    'header' => null,
    'mainClass' => null,
    'mainContainerClass' => null,
    'pageSlot' => null,
])
<div
    {{ $attributes->merge(['class' => 'flex flex-col min-h-screen bg-white dark:bg-gray-900']) }}
>
    <a class="sr-only" href="#main">
        {{ __('capell-frontend::generic.skip_link') }}
    </a>

    @if ($header)
        {{ $header }}
    @elseif ($header === null && (! isset($theme['meta']['header']) || $theme['meta']['header'] !== false))
        @if (! empty($theme['meta']['header_file']))
            <x-dynamic-component :component="$theme['meta']['header_file']" />
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
