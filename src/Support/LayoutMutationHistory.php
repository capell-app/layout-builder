<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;

final class LayoutMutationHistory
{
    /** @var array<int, LayoutBuilderStateData> */
    private array $undo = [];

    /** @var array<int, LayoutBuilderStateData> */
    private array $redo = [];

    public function __construct(private LayoutBuilderStateData $current) {}

    public function push(LayoutBuilderStateData $state): void
    {
        $this->undo[] = $this->current;
        $this->current = $state;
        $this->redo = [];
    }

    public function undo(): LayoutBuilderStateData
    {
        if (! $this->canUndo()) {
            return $this->current;
        }

        $this->redo[] = $this->current;
        $previous = array_pop($this->undo);

        if ($previous instanceof LayoutBuilderStateData) {
            $this->current = $previous;
        }

        return $this->current;
    }

    public function redo(): LayoutBuilderStateData
    {
        if (! $this->canRedo()) {
            return $this->current;
        }

        $this->undo[] = $this->current;
        $next = array_pop($this->redo);

        if ($next instanceof LayoutBuilderStateData) {
            $this->current = $next;
        }

        return $this->current;
    }

    public function canUndo(): bool
    {
        return $this->undo !== [];
    }

    public function canRedo(): bool
    {
        return $this->redo !== [];
    }

    public function current(): LayoutBuilderStateData
    {
        return $this->current;
    }
}
