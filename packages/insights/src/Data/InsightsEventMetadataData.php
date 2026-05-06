<?php

declare(strict_types=1);

namespace Capell\Insights\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class InsightsEventMetadataData extends Data
{
    public function __construct(
        public ?string $nearestLandmark = null,
    ) {}
}
