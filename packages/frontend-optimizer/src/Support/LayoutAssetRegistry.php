<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Support;

use InvalidArgumentException;

class LayoutAssetRegistry
{
    /** @var array<string, FrontendAssetSet> */
    private array $definitions = [];

    public function register(string $layoutKey, FrontendAssetSet $assets): self
    {
        $layoutKey = trim($layoutKey);

        throw_if($layoutKey === '', InvalidArgumentException::class, 'Layout key cannot be empty.');

        $this->definitions[$layoutKey] = $assets;

        return $this;
    }

    public function resolve(string $layoutKey): FrontendAssetSet
    {
        return $this->definitions[$layoutKey] ?? FrontendAssetSet::make();
    }

    /** @return array<string, FrontendAssetSet> */
    public function all(): array
    {
        return $this->definitions;
    }
}
