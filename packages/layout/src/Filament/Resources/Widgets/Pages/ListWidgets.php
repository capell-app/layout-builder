<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutResourceEnum;
use Capell\Layout\Filament\Actions\Page\CreateWidgetModalAction;
use Capell\Layout\Filament\Resources\WidgetResource\Pages\LayoutResource;
use Capell\Layout\Filament\Resources\Widgets\WidgetResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListWidgets extends ListRecords
{
    use ApplySearchRelationsTable;

    /** @return class-string<WidgetResource> */
    public static function getResource(): string
    {
        return CapellAdmin::getResource(LayoutResourceEnum::Widget->name);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-layout::generic.widgets_subheading');
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
            CreateWidgetModalAction::make()
                ->redirectAfterCreate(),
            ActionGroup::make([
                Action::make('layouts')
                    ->url(LayoutResource::getUrl())
                    ->label(LayoutResource::getNavigationLabel())
                    ->groupedIcon(LayoutResource::getNavigationIcon()),
            ]),
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
