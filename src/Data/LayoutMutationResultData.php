<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Spatie\LaravelData\Data;

final class LayoutMutationResultData extends Data
{
    /**
     * @param  array<int, LayoutDiagnosticData>  $diagnostics
     * @param  array<int, LayoutChangeData>  $changes
     */
    public function __construct(
        public LayoutBuilderStateData $state,
        public array $diagnostics = [],
        public array $changes = [],
    ) {}

    public function hasBlockingDiagnostics(): bool
    {
        foreach ($this->diagnostics as $diagnostic) {
            if ($diagnostic->isBlocking()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, LayoutChangeData>  $changes
     */
    public function withChanges(array $changes): self
    {
        return new self(
            state: $this->state,
            diagnostics: $this->diagnostics,
            changes: [...$this->changes, ...$changes],
        );
    }
}
