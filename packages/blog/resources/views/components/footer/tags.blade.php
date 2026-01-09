<?php

declare(strict_types=1);

?>

@props([
    'headingClass' => null,
    'linkClass' => 'hover:text-primary focus:bg-primary inline-flex items-center rounded-full bg-gray-600/75 px-3 py-2 text-sm font-medium leading-none tracking-wide text-[var(--color-footer)] no-underline focus:text-white',
])

<?php
use Capell\Blog\Services\Loader\TagLoader;
use Capell\Frontend\Facades\Frontend;

$language = Frontend::language();
$site = Frontend::site();
$tags = TagLoader::getTags($site, $language, limit: 5, hasArticles: true);
$tagPage = TagLoader::getTagResultsPage($site, $language);
?>

<div {{ $attributes->class(['footer-tags xl:w-[20%]']) }}>
    <h3 class="{{ $headingClass }} mb-4 dark:text-gray-100">
        {{ __('Tags') }}
    </h3>

    @if ($tags->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @foreach ($tags as $tag)
                @php($url = $tag->getPageUrl($tagPage, $language))
                <x-capell-blog::tag
                    :$url
                    wire:navigate
                    color-scheme="dark"
                    size="sm"
                    class="footer-tag"
                >
                    {{ $tag->getTranslation('name', $language->code) }}
                    <x-slot:count>
                        ({{ $tag->pages_count }})
                    </x-slot>
                </x-capell-blog::tag>
            @endforeach
        </div>
    @endif
</div>

<?php
