<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;
@endphp

@props([
    'class' => 'lightbox h-auto w-full cursor-pointer',
    'pageRecord' => Frontend::getPage(),
    'theme' => Frontend::getTheme(),
])
@if ($pageRecord->image)
    <x-dynamic-component
        data-lightbox="{{ \League\Glide\Urls\UrlBuilderFactory::create('/curator/', config('app.key'))->getUrl($pageRecord->image->path, ['width' => 1000, 'height' => 1000]) }}"
        format="webp"
        :component="$pageRecord->image->hasCuration('thumbnail') ? 'curator-curation' : 'curator-glider'"
        curation="thumbnail"
        :media="$pageRecord->image"
        :class="implode(' ', array_filter([$class, 'rounded' => $theme->meta['rounded'] ?? false]))"
        loading="lazy"
        :alt="strip_tags($pageRecord->image->alt ?: $pageRecord->translation->label)"
    />
@endif

<?php
