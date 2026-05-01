<?php

declare(strict_types=1);

namespace Capell\SeoTools\Enums;

use Filament\Support\Contracts\HasLabel;

enum SeoCheckKeyEnum: string implements HasLabel
{
    case MetaTitle = 'meta_title';
    case MetaDescription = 'meta_description';
    case DuplicateTitle = 'duplicate_title';
    case SocialImage = 'social_image';
    case Canonical = 'canonical';
    case Robots = 'robots';
    case ImageAltText = 'image_alt_text';
    case InternalLinks = 'internal_links';
    case Schema = 'schema';
    case BrokenLinks = 'broken_links';
    case Redirects = 'redirects';
    case TranslationCoverage = 'translation_coverage';
    case Sitemap = 'sitemap';
    case LlmsTxt = 'llms_txt';
    case SearchConsole = 'search_console';

    public function getLabel(): string
    {
        return __('capell-seo-tools::generic.seo_check_' . $this->value);
    }
}
