<?php

declare(strict_types=1);

namespace Capell\SeoTools\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

class SitemapUrlItemData extends Data
{
    public function __construct(
        public string $loc,
        #[WithCast(DateTimeInterfaceCast::class, DATE_ATOM)]
        #[WithTransformer(DateTimeInterfaceTransformer::class, DATE_ATOM)]
        public ?CarbonImmutable $lastmod = null,
        public ?string $changefreq = null,
        public ?string $priority = null,
    ) {}
}
