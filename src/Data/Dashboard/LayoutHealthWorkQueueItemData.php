<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\Dashboard;

use Spatie\LaravelData\Data;

final class LayoutHealthWorkQueueItemData extends Data
{
    public function __construct(
        public readonly string $kind,
        public readonly string $title,
        public readonly string $description,
        public readonly ?string $url,
    ) {}
}
