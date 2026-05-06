<?php

declare(strict_types=1);

namespace Capell\Insights\Data;

use Capell\Insights\Enums\InsightsEventType;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class InsightsJourneyStepData extends Data
{
    public function __construct(
        public int $sequence,
        public InsightsEventType $type,
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
