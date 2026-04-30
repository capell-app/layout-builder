<?php

declare(strict_types=1);

namespace Capell\Analytics\Data;

use Capell\Analytics\Enums\AnalyticsEventType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class AnalyticsJourneyStepData extends Data
{
    public function __construct(
        public int $sequence,
        public AnalyticsEventType $type,
        public string $url,
        public string $path,
        public ?string $title = null,
        public ?string $eventName = null,
        public ?string $label = null,
        public ?string $location = null,
        public ?CarbonImmutable $occurredAt = null,
        public ?int $secondsSincePreviousStep = null,
    ) {}
}
