<?php

declare(strict_types=1);

?>

@php
    use Capell\Frontend\Facades\Frontend;

    $page = Frontend::getPage();
    $theme = Frontend::getTheme();
@endphp

@props([
    'class' => 'lightbox h-auto w-full cursor-pointer',
])
@if ($page->image)
    <x-dynamic-component
        data-lightbox="{{ \League\Glide\Urls\UrlBuilderFactory::create('/curator/', config('app.key'))->getUrl($page->image->path, ['width' => 1000, 'height' => 1000]) }}"
        format="webp"
        :component="$page->image->hasCuration('thumbnail') ? 'curator-curation' : 'curator-glider'"
        curation="thumbnail"
        :media="$page->image"
        :class="implode(' ', array_filter([$class, 'rounded' => $theme->meta['rounded_images'] ?? false]))"
        loading="lazy"
        :alt="strip_tags($page->image->alt ?: $page->translation->label)"
    />
@endif

<?php
