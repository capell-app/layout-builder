<?php

declare(strict_types=1);

namespace Capell\Analytics\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class AnalyticsJourneyStepData extends Data
{
    public function __construct(
        public int $sequence,
        public string $path,
        public ?string $title = null,
        public ?string $eventName = null,
        public ?CarbonImmutable $occurredAt = null,
    ) {}
}
