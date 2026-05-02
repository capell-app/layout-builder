<?php

declare(strict_types=1);

namespace Capell\Mosaic\Data\Dashboard;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class ActivityItemData extends Data
{
    public function __construct(
        public readonly string $title,
        public readonly string $type,
        public readonly string $status,
        public readonly CarbonInterface $updatedAt,
    ) {}
}
