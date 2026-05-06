<?php

declare(strict_types=1);

namespace Capell\Insights\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class InsightsBeaconData extends Data
{
    /**
     * @param  list<InsightsEventData>  $events
     */
    public function __construct(
        public string $visitUuid,
        public string $url,
        public ?string $title = null,
        public ?int $siteId = null,
        public ?int $languageId = null,
        public array $events = [],
    ) {}
}
