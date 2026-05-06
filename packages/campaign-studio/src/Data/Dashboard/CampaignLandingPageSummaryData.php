<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Data\Dashboard;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class CampaignLandingPageSummaryData extends Data
{
    public function __construct(
        public int $landingPageId,
        public string $landingPageName,
        public string $campaignName,
        public int $conversions,
    ) {}
}
