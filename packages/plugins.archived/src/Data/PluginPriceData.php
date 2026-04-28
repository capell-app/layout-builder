<?php

declare(strict_types=1);

namespace Capell\Plugins\Data;

use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
final class PluginPriceData extends Data
{
    public function __construct(
        public readonly string $currency,
        public readonly ?int $monthly = null,
        public readonly ?int $yearly = null,
        public readonly ?int $once = null,
    ) {}
}
