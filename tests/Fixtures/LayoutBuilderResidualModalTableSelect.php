<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Tests\Fixtures;

use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Livewire\Filament\ModalTableSelect;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class LayoutBuilderResidualModalTableSelect extends ModalTableSelect
{
    /**
     * @return Builder<Model>|Builder<Page>|Builder<Widget>
     */
    public function exposeTableQuery(): Builder
    {
        return $this->getTableQuery();
    }

    public function exposeCanSubmitSelectedRecords(): bool
    {
        return $this->canSubmitSelectedRecords();
    }
}
