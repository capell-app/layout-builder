<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\WidgetResource\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutResourceEnum;
use Capell\Layout\Filament\Actions\Page\CreateWidgetAction;
use Capell\Layout\Filament\Resources\WidgetResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;

class ListWidgets extends ListRecords
{
    use ApplySearchRelationsTable;

    /** @return class-string<WidgetResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(LayoutResourceEnum::Widget->value);
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

    protected function getActions(): array
    {
        return [
            CreateWidgetAction::make(),
        ];
    }

    protected function getSearchRelationColumns(): array
    {
        return [
            'translations' => [
                'meta->actions',
                'contents' => 'json_data',
                'title',
            ],
        ];
    }
}
