<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Support;

use Capell\FrontendOptimizer\Data\FrontendAssetDefinitionData;
use Capell\FrontendOptimizer\Enums\AssetKind;
use Capell\FrontendOptimizer\Enums\AssetLoadingStrategy;
use Capell\FrontendOptimizer\Enums\AssetSlot;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class FrontendAssetSet
{
    /** @var array<int, FrontendAssetDefinitionData> */
    private array $assets = [];

    public static function make(): self
    {
        return new self;
    }

    public function css(
        string $handle,
        string $path,
        AssetLoadingStrategy $loadingStrategy = AssetLoadingStrategy::Deferred,
        AssetSlot $slot = AssetSlot::Base,
        bool $criticalEligible = false,
        ?string $packageName = null,
    ): self {
        return $this->asset(
            handle: $handle,
            path: $path,
            kind: AssetKind::Css,
            loadingStrategy: $loadingStrategy,
            slot: $slot,
            criticalEligible: $criticalEligible,
            packageName: $packageName,
        );
    }

    public function js(
        string $handle,
        string $path,
        AssetLoadingStrategy $loadingStrategy = AssetLoadingStrategy::Deferred,
        AssetSlot $slot = AssetSlot::Interactive,
        ?string $packageName = null,
    ): self {
        return $this->asset(
            handle: $handle,
            path: $path,
            kind: AssetKind::Js,
            loadingStrategy: $loadingStrategy,
            slot: $slot,
            criticalEligible: false,
            packageName: $packageName,
        );
    }

    public function merge(self $assetSet): self
    {
        foreach ($assetSet->all() as $asset) {
            $this->assets[] = $asset;
        }

        return $this;
    }

    /** @return Collection<int, FrontendAssetDefinitionData> */
    public function all(): Collection
    {
        return collect($this->assets)
            ->unique(static fn (FrontendAssetDefinitionData $asset): string => implode('|', [
                $asset->handle,
                $asset->path,
                $asset->kind->value,
                $asset->loadingStrategy->value,
                $asset->slot->value,
            ]))
            ->sortBy(static fn (FrontendAssetDefinitionData $asset): string => $asset->handle)
            ->values();
    }

    private function asset(
        string $handle,
        string $path,
        AssetKind $kind,
        AssetLoadingStrategy $loadingStrategy,
        AssetSlot $slot,
        bool $criticalEligible,
        ?string $packageName,
    ): self {
        $handle = trim($handle);
        $path = trim($path);

        throw_if($handle === '', InvalidArgumentException::class, 'Frontend asset handle cannot be empty.');
        throw_if($path === '', InvalidArgumentException::class, 'Frontend asset path cannot be empty.');
        throw_if(
            $kind === AssetKind::Js && $loadingStrategy === AssetLoadingStrategy::Critical,
            InvalidArgumentException::class,
            'JavaScript assets cannot use the critical loading strategy.',
        );

        $this->assets[] = new FrontendAssetDefinitionData(
            handle: $handle,
            path: $path,
            kind: $kind,
            loadingStrategy: $loadingStrategy,
            slot: $slot,
            criticalEligible: $criticalEligible,
            packageName: $packageName,
        );

        return $this;
    }
}
