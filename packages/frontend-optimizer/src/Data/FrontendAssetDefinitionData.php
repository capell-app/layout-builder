<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Data;

use Capell\FrontendOptimizer\Enums\AssetKind;
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Enums\AssetSlot;
use Spatie\LaravelData\Data;

class FrontendAssetDefinitionData extends Data
{
    public function __construct(
        public string $handle,
        public string $path,
        public AssetKind $kind,
        public AssetLoadingStrategy $loadingStrategy,
        public AssetSlot $slot = AssetSlot::Base,
        public bool $criticalEligible = false,
        public ?string $packageName = null,
    ) {}

    /** @return array<string, mixed> */
    public function signature(): array
    {
        return [
            'critical_eligible' => $this->criticalEligible,
            'handle' => $this->handle,
            'kind' => $this->kind->value,
            'loading_strategy' => $this->loadingStrategy->value,
            'package_name' => $this->packageName,
            'path' => $this->path,
            'slot' => $this->slot->value,
        ];
    }
}
