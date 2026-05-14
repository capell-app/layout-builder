<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutBuilderStateData extends Data
{
    public function __construct(
        public array $containers,
        public array $assets,
        public array $originalAssets,
        public array $selectedRecords,
    ) {}

    public static function fromLivewire(
        ?array $containers,
        array $assets,
        ?array $originalAssets,
        array $selectedRecords,
    ): self {
        return new self(
            containers: $containers ?? [],
            assets: $assets,
            originalAssets: $originalAssets ?? [],
            selectedRecords: $selectedRecords,
        );
    }

    public function toLivewirePayload(): array
    {
        return [
            'containers' => $this->containers,
            'assets' => $this->assets,
            'originalAssets' => $this->originalAssets,
            'selectedRecords' => $this->selectedRecords,
        ];
    }
}
