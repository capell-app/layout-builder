<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Assets\Table;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutModelEnum;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;

class ContentsTable extends AbstractAssetsTable
{
    public string $type = 'content';

    public function table(Table $table): Table
    {
        return parent::table(
            \Capell\Layout\Filament\Resources\Contents\Tables\ContentsTable::configure($table)
        );
    }

    public function getFilteredTableQuery(): Builder
    {
        $query = parent::getFilteredTableQuery();

        if (isset($this->getTableFilterState('filter')['language_id'])) {
            $language_id = $this->getTableFilterState('filter')['language_id'];
        } else {
            $language_id = CapellCore::getModel(ModelEnum::Language)::query()->default()->value('id');
        }

        $query->with([
            'translation' => fn (BuilderContract $query) => $query->where('language_id', (int) $language_id),
        ]);

        return $query;
    }

    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\Layout\Models\Content> $model */
        $model = CapellCore::getModel(LayoutModelEnum::Content->name);

        return $model::with([
            'ancestors',
            'translations.language',
            'image',
            'type',
        ]);
    }
}
