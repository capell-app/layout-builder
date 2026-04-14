<?php

declare(strict_types=1);

use Capell\Frontend\Facades\Frontend;

$page = Frontend::page();

?>

@props([
    'author',
])
@if ($author)
    <div {{ $attributes->class('page-author flex items-center gap-5') }}>
        @if (method_exists($author, 'profileImage') && $author->profileImage)
            <x-capell::media
                :media="$author->profileImage"
                fit="crop"
                :width="120"
                class="h-12 w-12 flex-shrink-0 rounded-lg bg-white object-cover object-center"
                loading="lazy"
            />
        @endif

        <div class="leading-tight tracking-wide">
            <span class="font-medium text-gray-500">
                {{ $author->name }}
            </span>

            @if ($author->bio)
                <div
                    @class([
                        'prose text-sm font-light leading-tight text-gray-500 [&>:first-child]:mt-0 [&>:last-child]:mb-0',
                        'dark:prose-invert' => $theme->withDarkMode,
                    ])
                >
                    {!! $author->bio !!}
                </div>
            @endif
        </div>
    </div>
@endif
