<?php

declare(strict_types=1);

namespace Capell\Campaigns\Data\Dashboard;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class CampaignConversionSummaryData extends Data
{
    public function __construct(
        public int $campaignGroupId,
        public string $campaignName,
        public int $conversions,
        public int $visits,
        public float $conversionRate,
    ) {}
}
