<?php

declare(strict_types=1);

namespace Capell\CampaignStudio\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class CampaignCtaActionData extends Data
{
    public function __construct(
        public string $label,
        public string $url,
        public ?string $style = 'primary',
        public ?string $goalKey = null,
        public ?UtmData $utm = null,
    ) {}
}
