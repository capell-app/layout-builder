<?php

declare(strict_types=1);

namespace Capell\Mosaic\Support;

use Capell\Mosaic\Data\LayoutAssetBridgeData;

class LayoutAssetBridgeRegistry
{
    /**
     * @var array<string, LayoutAssetBridgeData>
     */
    private array $assets = [];

    public function register(LayoutAssetBridgeData $asset): void
    {
        $this->assets[$asset->key] = $asset;
    }

    /**
     * @return array<string, LayoutAssetBridgeData>
     */
    public function all(): array
    {
        return $this->assets;
    }

    public function get(string $key): ?LayoutAssetBridgeData
    {
        return $this->assets[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->assets[$key]);
    }
}
