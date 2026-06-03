<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\LayoutBuilder\Filament\Components\Forms\AssetsRepeater as LayoutBuilderAssetsRepeater;
use Closure;
use Filament\Schemas\Schema;
use Override;

final class LayoutBuilderAssetsRepeaterHarness extends LayoutBuilderAssetsRepeater
{
    /** @var array<array-key, mixed> */
    public array $rawState = [];

    public ?Schema $lastChildSchema = null;

    public bool $afterStateUpdatedCalled = false;

    public bool $partiallyRendered = false;

    public bool $collapsedCalled = false;

    #[Override]
    public function getRawState(): mixed
    {
        return $this->rawState;
    }

    #[Override]
    public function rawState(mixed $state): static
    {
        $this->rawState = is_array($state) ? $state : [];

        return $this;
    }

    /**
     * @return array<array-key, mixed>
     */
    #[Override]
    public function getRawItemState(string $key): array
    {
        $state = $this->rawState[$key] ?? [];

        return is_array($state) ? $state : [];
    }

    #[Override]
    public function getChildSchema($key = null): Schema
    {
        $state = is_int($key) || is_string($key)
            ? ($this->rawState[$key] ?? [])
            : [];

        $this->lastChildSchema = Schema::make(new LayoutBuilderCoverageSchemaHarness)
            ->operation('edit')
            ->statePath('state')
            ->rawState(is_array($state) ? $state : []);

        return $this->lastChildSchema;
    }

    #[Override]
    public function collapsed(bool|Closure $condition = true, bool $shouldMakeComponentCollapsible = true): static
    {
        $this->collapsedCalled = true;

        return parent::collapsed($condition, $shouldMakeComponentCollapsible);
    }

    #[Override]
    public function callAfterStateUpdated(bool $shouldBubbleToParents = true): static
    {
        $this->afterStateUpdatedCalled = true;

        return $this;
    }

    #[Override]
    public function partiallyRender(): void
    {
        $this->partiallyRendered = true;
    }
}
