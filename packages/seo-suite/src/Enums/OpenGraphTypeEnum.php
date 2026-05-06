<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Enums;

use Filament\Support\Contracts\HasLabel;

enum OpenGraphTypeEnum: string implements HasLabel
{
    case Website = 'website';
    case Article = 'article';
    case Product = 'product';
    case Profile = 'profile';

    /**
     * Map a Schema.org type to an Open Graph type.
     */
    public static function fromSchemaType(?string $configuratorType): self
    {
        if ($configuratorType === null) {
            return self::Website;
        }

        return match ($configuratorType) {
            'Article', 'BlogPosting', 'NewsArticle', 'TechArticle', 'Report' => self::Article,
            'Product' => self::Product,
            'Person', 'ProfilePage' => self::Profile,
            default => self::Website,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Website => __('capell::generic.og_type_website'),
            self::Article => __('capell::generic.og_type_article'),
            self::Product => __('capell::generic.og_type_product'),
            self::Profile => __('capell::generic.og_type_profile'),
        };
    }

    public function isArticle(): bool
    {
        return $this === self::Article;
    }
}
