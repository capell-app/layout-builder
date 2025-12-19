<?php

declare(strict_types=1);

?>

@props([
'linkClass' => 'hover:text-primary focus:bg-primary inline-flex items-center rounded-full bg-gray-600/75 px-3 py-2 text-sm font-medium leading-none tracking-wide text-[var(--color-footer)] no-underline focus:text-white',
])

<?php
    use Capell\Blog\Services\Loader\TagLoader;
use Capell\Frontend\Facades\Frontend;

$language = Frontend::language();
$site = Frontend::site();
$tags = TagLoader::getTags($site, $language, hasArticles: true, limit: 5);
$tagPage = TagLoader::getTagResultsPage($site, $language);
?>

<div {{ $attributes }}>
    {{ $heading ?? '' }}

    @if ($tags->isNotEmpty())
        <div class="flex flex-wrap gap-2">
            @foreach ($tags as $tag)
                @php($url = $tag->getPageUrl($tagPage, $language))
                <x-capell-blog::tag
                    :$url
                    wire:navigate
                    color-scheme="dark"
                    size="sm"
                >
                    {{ $tag->getTranslation('name', $language->code) }}
                    ({{ $tag->pages_count }})
                </x-capell-blog::tag>
            @endforeach
        </div>
    @endif
</div>

<?php
