<?php

declare(strict_types=1);

namespace Capell\FrontendOptimizer\Support;

use Closure;
use InvalidArgumentException;

class WidgetAssetRegistry
{
    /** @var array<string, array{assets: FrontendAssetSet, condition: Closure|null}> */
    private array $definitions = [];

    public function register(string $widgetType, FrontendAssetSet $assets, ?Closure $condition = null): self
    {
        $widgetType = trim($widgetType);

        throw_if($widgetType === '', InvalidArgumentException::class, 'Widget type cannot be empty.');

        $this->definitions[$widgetType] = [
            'assets' => $assets,
            'condition' => $condition,
        ];

        return $this;
    }

    /** @param array<string, mixed> $widgetData */
    public function resolve(string $widgetType, array $widgetData = []): FrontendAssetSet
    {
        $definition = $this->definitions[$widgetType] ?? null;

        if ($definition === null) {
            return FrontendAssetSet::make();
        }

        $condition = $definition['condition'];

        if ($condition instanceof Closure && $condition($widgetData) !== true) {
            return FrontendAssetSet::make();
        }

        return $definition['assets'];
    }

    /** @return array<string, array{assets: FrontendAssetSet, condition: Closure|null}> */
    public function all(): array
    {
        return $this->definitions;
    }
}
