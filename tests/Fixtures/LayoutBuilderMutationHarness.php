<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Override;

final class LayoutBuilderMutationHarness extends LayoutBuilder
{
    /** @var array<int, string> */
    public array $events = [];

    /**
     * @param  array<array-key, mixed>  $containerWidgets
     */
    public function seedContainerWidgets(array $containerWidgets): void
    {
        $this->containerWidgets = $containerWidgets;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function containerWidgetsForTest(): array
    {
        return $this->containerWidgets;
    }

    public function setupContainersFromLayoutForTest(): void
    {
        $this->setupContainers();
    }

    public function insertContainerWidgetAtPositionForTest(string $containerKey, int $originalIndex, int $position): void
    {
        $this->insertContainerWidgetAtPosition($containerKey, $originalIndex, $position);
    }

    public function normalizeContainerWidgetOccurrencesForTest(string $containerKey): void
    {
        $this->normalizeContainerWidgetOccurrences($containerKey);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function containerWidgetKeysForTest(): array
    {
        return $this->getContainerWidgetKeys();
    }

    #[Override]
    public function assertCanUpdateLayout(): void
    {
        $this->events[] = 'assertCanUpdateLayout';
    }

    #[Override]
    public function ensureLoaded(): void
    {
        $this->events[] = 'ensureLoaded';
    }

    #[Override]
    public function layoutUpdated(bool $modified = true): void
    {
        $this->layoutModified = $modified;
        $this->events[] = 'layoutUpdated';
    }
}
