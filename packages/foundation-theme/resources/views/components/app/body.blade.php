@props([
    'bodyClass' => null,
    'language',
    'layout',
    'pageRecord',
    'site',
    'theme',
])

@php
    use Capell\Frontend\Facades\Frontend;

    $runtimeManifest = Frontend::getFrontendData('runtimeManifest');
    $usesAlpine = $runtimeManifest?->usesAlpine ?? false;
@endphp

<body
    @class([
        'layout-' . $layout->key,
        $layout->getMeta('body_class'),
        $theme->getMeta('body_class'),
        $bodyClass ?? 'min-h-screen min-w-[320px] overflow-x-hidden font-sans font-normal leading-normal text-gray-800 antialiased dark:bg-gray-950 dark:text-gray-100',
    ])
    @if ($usesAlpine)
        x-data="{ showLightbox: false }"
        :class="{ 'overflow-hidden': showLightbox }"
        @keydown.escape="showLightbox = false"
    @endif
>
    {{ $slot }}
</body>
