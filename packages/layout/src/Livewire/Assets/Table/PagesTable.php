<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Assets\Table;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;

class PagesTable extends AbstractAssetsTable
{
    public string $type = 'page';

    public function table(Table $table): Table
    {
        return parent::table(
            \Capell\Admin\Filament\Resources\Pages\Tables\PagesTable::configure($table)
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
            'pageUrl' => fn (BuilderContract $query) => $query->where('language_id', (int) $language_id),
        ]);

        return $query;
    }

    protected function getTableQuery(): Builder
    {
        /* @var class-string<\Capell\Core\Models\Page> $model */
        $model = CapellCore::getModel(ModelEnum::Page);

        return $model::with([
            'translations.language',
            'ancestors.type',
            'creator',
            'image',
            'editor',
            'site.siteDomains',
            'type',
        ])
            ->when(
                $this->arguments['pageId'] ?? null,
                fn (BuilderContract $query) => $query->whereKeyNot($this->arguments['pageId'])
            );
    }
}
