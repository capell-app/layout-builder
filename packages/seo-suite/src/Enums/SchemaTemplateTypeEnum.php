<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Enums;

use Filament\Support\Contracts\HasLabel;

enum SchemaTemplateTypeEnum: string implements HasLabel
{
    case Article = 'Article';
    case WebPage = 'WebPage';
    case FAQ = 'FAQPage';
    case HowTo = 'HowTo';
    case Event = 'Event';
    case LocalBusiness = 'LocalBusiness';
    case Product = 'Product';
    case Video = 'VideoObject';
    case Organization = 'Organization';

    public function getLabel(): string
    {
        return __('capell-seo-suite::generic.schema_template_type_' . str($this->name)->snake()->value());
    }

    /**
     * @return list<string>
     */
    public function compatibleSchemaTypes(): array
    {
        return match ($this) {
            self::Article => ['Article', 'BlogPosting', 'NewsArticle', 'TechArticle', 'Report'],
            self::Video => ['VideoObject', 'Video'],
            default => [$this->value],
        };
    }

    public function matchesSchemaType(?string $schemaType): bool
    {
        if ($schemaType === null || $schemaType === '') {
            return $this === self::WebPage;
        }

        return in_array($schemaType, $this->compatibleSchemaTypes(), true);
    }
}
