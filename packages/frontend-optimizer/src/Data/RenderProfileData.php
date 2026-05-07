<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Data;

use Capell\FrontendOptimizer\Enums\OptimizationScope;
use Spatie\LaravelData\Data;

class RenderProfileData extends Data
{
    /**
     * @param  array<string, mixed>  $signature
     * @param  array<int, FrontendAssetDefinitionData>  $assets
     */
    public function __construct(
        public string $hash,
        public OptimizationScope $scope,
        public array $signature,
        public array $assets,
        public ?string $label = null,
    ) {}

    /** @return array<string, mixed> */
    public function manifest(): array
    {
        return [
            'hash' => $this->hash,
            'label' => $this->label,
            'scope' => $this->scope->value,
            'assets' => array_map(
                static fn (FrontendAssetDefinitionData $asset): array => $asset->signature(),
                $this->assets,
            ),
        ];
    }
}
