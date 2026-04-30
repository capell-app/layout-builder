<?php

declare(strict_types=1);

namespace Capell\Analytics\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class AnalyticsEventMetadataData extends Data
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public ?string $nearestLandmark = null,
        public array $attributes = [],
    ) {}
}
