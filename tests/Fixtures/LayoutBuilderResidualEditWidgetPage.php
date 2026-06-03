<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Contracts\Support\Htmlable;
use Override;

final class LayoutBuilderResidualEditWidgetPage extends EditWidget
{
    public function __construct(public Widget $testRecord)
    {
        $this->record = $testRecord;
    }

    #[Override]
    public function getRecord(): Widget
    {
        return $this->testRecord;
    }

    #[Override]
    public function getRecordTitle(): string
    {
        return $this->testRecord->name;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function exposeRelationManagers(): array
    {
        return $this->getRelationManagers();
    }

    public function exposeSubheading(): string
    {
        $subheading = $this->getSubheading();

        return $subheading instanceof Htmlable ? $subheading->toHtml() : (string) $subheading;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function exposeBaseHeaderActions(): array
    {
        return $this->getBaseHeaderActions();
    }

    /**
     * @return array<array-key, mixed>
     */
    public function exposeRecordSwitcherColumns(): array
    {
        return $this->getRecordSwitcherColumns();
    }

    /**
     * @return array<array-key, mixed>
     */
    public function exposeRecordSwitcherSearchColumns(): array
    {
        return self::getRecordSwitcherSearchColumns();
    }

    public function exposeSelectChangerItemLabel(Widget $widget): string
    {
        return $this->selectChangerItemLabel($widget);
    }
}
