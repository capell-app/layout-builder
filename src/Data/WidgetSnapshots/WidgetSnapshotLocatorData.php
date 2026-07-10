<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\WidgetSnapshots;

use Spatie\LaravelData\Data;

final class WidgetSnapshotLocatorData extends Data
{
    public function __construct(
        public int $version,
        public string $purpose,
        public int $snapshotId,
        public string $pageableType,
        public int $pageableId,
        public string $targetInstanceId,
    ) {}
}
