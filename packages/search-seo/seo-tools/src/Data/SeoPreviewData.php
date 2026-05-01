<?php

declare(strict_types=1);

namespace Capell\SeoTools\Data;

use Spatie\LaravelData\Data;

class SeoPreviewData extends Data
{
    public function __construct(
        public string $title,
        public string $description,
        public string $url,
        public ?string $imageUrl = null,
        public ?string $siteName = null,
    ) {}
}
