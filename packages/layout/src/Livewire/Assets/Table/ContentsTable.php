<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Assets\Table;

use Capell\Admin\Filament\Components\Tables\Columns\CuratorColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\TypeNameColumn;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Enums\TagTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Enums\LayoutModelEnum;
use Capell\Layout\Filament\Components\Tables\Columns\Content\ContentNameColumn;
use Capell\Layout\Filament\Resources\ContentResource;
use Filament\Tables;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;

class ContentsTable extends AbstractAssetsTable
{
    public string $type = 'content';

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

    protected function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            ContentNameColumn::make('name'),
            Tables\Columns\TextColumn::make('translation.title')
                ->label(__('capell-admin::table.title'))
                ->searchable()
                ->html()
                ->toggleable(isToggledHiddenByDefault: true),
            LanguagesColumn::make('translations.language'),
            Tables\Columns\TextColumn::make('parent.name')
                ->label(__('capell-admin::table.parent'))
                ->searchable()
                ->sortable()
                ->limit(60)
                ->linkRecord()
                ->toggleable(isToggledHiddenByDefault: true),
            TypeNameColumn::make('type.name'),
            Tables\Columns\SpatieTagsColumn::make('tags')
                ->label(__('capell-admin::table.tags'))
                ->type(TagTypeEnum::CONTENT->value)
                ->toggleable(isToggledHiddenByDefault: true),
            CuratorColumn::make('meta.image')
                ->relationship('image')
                ->toggleable(),
        ];
    }

    protected function getTableFilters(): array
    {
        return ContentResource::getTableFilters();
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
