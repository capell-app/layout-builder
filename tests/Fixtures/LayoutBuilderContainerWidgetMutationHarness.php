<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\LayoutBuilder\Livewire\Filament\LayoutBuilder;
use Capell\LayoutBuilder\Models\Widget;
use Override;

final class LayoutBuilderContainerWidgetMutationHarness extends LayoutBuilder
{
    #[Override]
    public function assertCanUpdateLayout(): void {}

    #[Override]
    public function assertCanEditContent(): void {}

    /**
     * @param  array<string, array<int, Widget>>  $containerWidgets
     */
    public function setContainerWidgets(array $containerWidgets): void
    {
        $this->containerWidgets = $containerWidgets;
    }

    public function exposeNormalizeContainerWidgetOccurrences(string $containerKey): void
    {
        $this->normalizeContainerWidgetOccurrences($containerKey);
    }

    /**
     * @return array<array-key, mixed>
     */
    public function exposeGetContainerWidgetKeys(): array
    {
        return $this->getContainerWidgetKeys();
    }

    public function exposeGetLastContainerWidgetOccurrence(string $containerKey, string $widgetKey, ?int $compareIndex = null): int
    {
        return $this->getLastContainerWidgetOccurrence($containerKey, $widgetKey, $compareIndex);
    }

    #[Override]
    protected function assertCanEditLayout(): void {}
}
