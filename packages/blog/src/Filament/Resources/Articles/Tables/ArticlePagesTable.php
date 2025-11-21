<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Articles\Tables;

use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Admin\Filament\Resources\Pages\Tables\PagesTable;
use Capell\Blog\Models\Tag;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore; // adjust if different namespace
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ArticlePagesTable implements TableConfigurator
{
    public static function configure(Table $table): Table
    {
        $baseTable = PagesTable::configure($table);

        $filters = PagesTable::getBaseTableFilters();
        $filters = self::mutateBaseFilters($filters);

        return $baseTable->filters($filters);
    }

    /**
     * Mutate the base filters adding the tags filter without subclassing PagesTable.
     */
    protected static function mutateBaseFilters(array $filters): array
    {
        $filters[] = SelectFilter::make('tags')
            ->label(__('capell-layout::form.tags'))
            ->searchable()
            ->preload()
            ->relationship(
                name: 'tags',
                titleAttribute: 'name',
                modifyQueryUsing: function (Builder $query, HasTable $livewire): void {
                    $site_id = $livewire->activeTab;

                    if (in_array($site_id, [null, '', '0'], true)) {
                        $query->with('site')->orderBy('site_id');
                    } else {
                        $query->where(fn (Builder $q) => $q->where('site_id', $site_id)->orWhereNull('site_id'));
                        $query->whereHas('pages', fn (BuilderContract $q) => $q->where('site_id', $site_id));
                    }

                    $languageId = $livewire->getTableFilterState('filter')['language_id'] ?? null;
                    if ($languageId) {
                        $code = CapellCore::getModel(ModelEnum::Language)::find($languageId, 'code')?->code;
                        if ($code) {
                            $query->whereRaw('JSON_EXTRACT(`tags`.`name`, ' . DB::getPdo()->quote('$.' . $code) . ') IS NOT NULL');
                        }
                    }
                },
            )
            ->query(function (Builder $query, array $data): Builder {
                $value = $data['value'] ?? null;

                return $query->when(
                    $value,
                    fn (Builder $query) => $query->whereHas('tags', fn (BuilderContract $q) => $q->where('tags.id', (int) $value)),
                );
            })
            ->indicateUsing(function (array $state): array {
                $indicators = [];
                $value = $state['value'] ?? null;
                if ($value) {
                    $indicators['tags'] = __(
                        'capell-layout::filter.tag',
                        ['search' => Tag::query()->find($value)?->name],
                    );
                }

                return $indicators;
            });

        return $filters;
    }
}
