<?php
use Capell\Frontend\Facades\Frontend;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

$site = Frontend::site();

/** @var Media $media */
?>

@props([
    'media',
])

@if ($media->extension === 'svg')
    <x-capell::media.svg
        :path="$media->getPath()"
        :attributes="$attributes"
    />
@else
    <x-capell::media
        :media="$media"
        :alt="$site->translation->title"
        :attributes="$attributes"
    />
@endif
