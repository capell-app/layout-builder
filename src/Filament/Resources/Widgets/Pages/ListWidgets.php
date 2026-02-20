<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\Pages;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Capell\Layout\Enums\ResourceEnum as LayoutResourceEnum;
use Capell\Layout\Filament\Actions\CreateWidgetAction;
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
        return CapellAdmin::getResource(LayoutResourceEnum::Widget);
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
            /** @var class-string<Language> $model */
            $model = CapellCore::getModel(ModelEnum::Language);

            $language_id = $model::query()->default()->value('id');
        }

        $query->with([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', (int) $language_id),
        ]);

        return $query;
    }

    protected function getActions(): array
    {
        $layoutResource = CapellAdmin::getResource(ResourceEnum::Layout);

        return [
            CreateWidgetAction::make()
                ->redirectAfterCreate(),
            ActionGroup::make([
                Action::make('layouts')
                    ->url($layoutResource::getUrl())
                    ->label($layoutResource::getNavigationLabel())
                    ->groupedIcon($layoutResource::getNavigationIcon()),
            ]),
        ];
    }

    protected function getSearchRelationColumns(): array
    {
        return [
            'translations' => [
                'meta->actions',
                'content',
                'title',
            ],
        ];
    }
}
