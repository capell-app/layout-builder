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

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public static function fromSnapshot(array $snapshot): self
    {
        return new self(
            containers: is_array($snapshot['containers'] ?? null) ? $snapshot['containers'] : [],
            assets: is_array($snapshot['assets'] ?? null) ? $snapshot['assets'] : [],
            originalAssets: is_array($snapshot['originalAssets'] ?? null) ? $snapshot['originalAssets'] : [],
            selectedRecords: is_array($snapshot['selectedRecords'] ?? null) ? $snapshot['selectedRecords'] : [],
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
