<?php

declare(strict_types=1);

namespace Capell\Campaigns\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class ConversionAttributionData extends Data
{
    public function __construct(
        public ?string $landingUrl = null,
        public ?string $referrerUrl = null,
        public ?string $utmSource = null,
        public ?string $utmMedium = null,
        public ?string $utmCampaign = null,
        public ?string $utmTerm = null,
        public ?string $utmContent = null,
        public ?string $eventName = null,
        public ?string $eventLabel = null,
        public ?string $eventLocation = null,
        public ?string $firstTouchCampaign = null,
        public ?string $lastTouchCampaign = null,
    ) {}
}
