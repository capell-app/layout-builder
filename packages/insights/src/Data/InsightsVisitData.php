<?php

declare(strict_types=1);

namespace Capell\Insights\Data;

use Capell\Insights\Enums\InsightsConsentRegion;
use Capell\Insights\Enums\InsightsConsentStatus;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class InsightsVisitData extends Data
{
    public function __construct(
        public string $uuid,
        public string $landingUrl,
        public InsightsConsentRegion $consentRegion = InsightsConsentRegion::Unknown,
        public InsightsConsentStatus $consentStatus = InsightsConsentStatus::Pending,
        public ?int $siteId = null,
        public ?int $languageId = null,
        public ?string $referrerUrl = null,
        public ?string $utmSource = null,
        public ?string $utmMedium = null,
        public ?string $utmCampaign = null,
        public ?CarbonImmutable $startedAt = null,
        public ?CarbonImmutable $lastSeenAt = null,
    ) {}
}
