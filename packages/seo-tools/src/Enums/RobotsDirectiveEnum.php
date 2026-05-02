<?php

declare(strict_types=1);

namespace Capell\SeoTools\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum RobotsDirectiveEnum: string implements HasDescription, HasLabel
{
    case NoIndex = 'noindex';
    case NoFollow = 'nofollow';
    case NoArchive = 'noarchive';
    case NoSnippet = 'nosnippet';
    case MaxSnippet = 'max-snippet:-1';
    case MaxImagePreview = 'max-image-preview:large';
    case MaxVideoPreview = 'max-video-preview:-1';

    public function getLabel(): string
    {
        return match ($this) {
            self::NoIndex => __('capell::generic.robots_noindex'),
            self::NoFollow => __('capell::generic.robots_nofollow'),
            self::NoArchive => __('capell::generic.robots_noarchive'),
            self::NoSnippet => __('capell::generic.robots_nosnippet'),
            self::MaxSnippet => __('capell::generic.robots_max_snippet'),
            self::MaxImagePreview => __('capell::generic.robots_max_image_preview'),
            self::MaxVideoPreview => __('capell::generic.robots_max_video_preview'),
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::NoIndex => __('capell::generic.robots_noindex_description'),
            self::NoFollow => __('capell::generic.robots_nofollow_description'),
            self::NoArchive => __('capell::generic.robots_noarchive_description'),
            self::NoSnippet => __('capell::generic.robots_nosnippet_description'),
            self::MaxSnippet => __('capell::generic.robots_max_snippet_description'),
            self::MaxImagePreview => __('capell::generic.robots_max_image_preview_description'),
            self::MaxVideoPreview => __('capell::generic.robots_max_video_preview_description'),
        };
    }
}
