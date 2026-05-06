<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Enums;

enum SchemaEntityTypeEnum: string
{
    case Organization = 'Organization';
    case WebSite = 'WebSite';
    case WebPage = 'WebPage';
    case Article = 'Article';
    case BlogPosting = 'BlogPosting';
    case NewsArticle = 'NewsArticle';
    case BreadcrumbList = 'BreadcrumbList';
    case Person = 'Person';
    case ImageObject = 'ImageObject';

    /**
     * Determine the entity type from a Schema.org @type string.
     */
    public static function fromSchemaType(string $configuratorType): self
    {
        return self::tryFrom($configuratorType) ?? self::WebPage;
    }

    /**
     * Generate a stable @id URI for this entity type.
     */
    public function toId(string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . '/#' . $this->value;
    }

    /**
     * Whether this entity type is a page-level type (as opposed to site-level).
     */
    public function isPageLevel(): bool
    {
        return match ($this) {
            self::Organization, self::WebSite, self::Person => false,
            default => true,
        };
    }
}
