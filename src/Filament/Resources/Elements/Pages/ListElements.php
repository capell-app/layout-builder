<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Elements\Pages;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Filament\Concerns\ApplySearchRelationsTable;
use Capell\Admin\Support\AdminSurfaceLookup;
use Capell\Core\Models\Language;
use Capell\LayoutBuilder\Enums\ResourceEnum as LayoutResourceEnum;
use Capell\LayoutBuilder\Filament\Actions\CreateElementAction;
use Capell\LayoutBuilder\Filament\Resources\Elements\ElementResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Override;

class ListElements extends ListRecords
{
    use ApplySearchRelationsTable;

    /** @return class-string<ElementResource> */
    #[Override]
    public static function getResource(): string
    {
        return AdminSurfaceLookup::resource(LayoutResourceEnum::Element);
    }

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        return __('capell-admin::generic.element_info');
    }

    public function getFilteredTableQuery(): Builder
    {
        $query = parent::getFilteredTableQuery();

        if (isset($this->getTableFilterState('filter')['language_id'])) {
            $language_id = $this->getTableFilterState('filter')['language_id'];
        } else {
            /** @var class-string<Language> $model */
            $model = Language::class;

            $language_id = $model::query()->default()->value('id');
        }

        $query->with([
            'translation' => fn (BuilderContract $query): BuilderContract => $query->where('language_id', $language_id),
        ]);

        return $query;
    }

    #[Override]
    protected function getActions(): array
    {
        $layoutResource = AdminSurfaceLookup::resource(ResourceEnum::Layout);

        return [
            CreateElementAction::make('create')
                ->redirectAfterCreate(),
            Action::make('layouts')
                ->url($layoutResource::getUrl())
                ->label($layoutResource::getNavigationLabel())
                ->groupedIcon($layoutResource::getNavigationIcon()),
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
