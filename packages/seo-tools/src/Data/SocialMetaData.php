<?php

declare(strict_types=1);

namespace Capell\SeoTools\Data;

use Capell\SeoTools\Enums\OpenGraphTypeEnum;
use Spatie\LaravelData\Data;

class SocialMetaData extends Data
{
    public function __construct(
        public string $title,
        public string $description,
        public ?string $imageUrl,
        public ?int $imageWidth,
        public ?int $imageHeight,
        public ?string $imageMimeType,
        public ?string $imageAlt,
        public OpenGraphTypeEnum $ogType,
        public string $url,
        public string $locale,
        public ?string $siteName,
        public ?string $twitterHandle,
        public string $twitterCard = 'summary_large_image',
        public ?string $articlePublishedTime = null,
        public ?string $articleModifiedTime = null,
        public ?string $articleAuthor = null,
    ) {}
}
