<?php

declare(strict_types=1);

namespace Capell\Layout\Livewire\Assets\Table;

use Capell\Admin\Filament\Components\Tables\Columns\CuratorColumn;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageStatusColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Components\Tables\Columns\TypeNameColumn;
use Capell\Admin\Filament\Components\Tables\Filters\DateFilter;
use Capell\Admin\Filament\Components\Tables\Filters\DraftFilter;
use Capell\Core\Enums\ModelEnum;
use Capell\Core\Enums\TagTypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagesTable extends AbstractAssetsTable
{
    public string $type = 'page';

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

    protected function getTableColumns(): array
    {
        return [
            IdentifierColumn::make('id'),
            PageNameColumn::make('name'),
            CuratorColumn::make('image')
                ->relationship('image')
                ->toggleable(),
            Tables\Columns\TextColumn::make('translation.contents')
                ->label(__('capell-admin::table.content'))
                ->sortable()
                ->searchable()
                ->limit(200)
                ->wrap()
                ->size('xs')
                ->color('gray')
                ->html()
                ->listWithLineBreaks()
                ->formatStateUsing(function (Tables\Columns\TextColumn $column, Models\Page $record): string {
                    if (! $record->translation->contents) {
                        return '';
                    }

                    $contents = '';

                    foreach ($record->translation->contents as $content) {
                        if (! isset($content['data']['content'])) {
                            continue;
                        }

                        $contents .= strip_tags((string) $content['data']['content']);

                        if (Str::length($contents) >= $column->getCharacterLimit()) {
                            break;
                        }
                    }

                    return Str::limit($contents, $column->getCharacterLimit(), $column->getCharacterLimitEnd());
                })
                ->toggleable(isToggledHiddenByDefault: true),
            LanguagesColumn::make('translations.language'),
            SiteColumn::make('site.name')
                ->visible(fn ($livewire): bool => empty($livewire->getTableFilterState('filter')['site_id'])),
            Tables\Columns\TextColumn::make('layout.name')
                ->label(__('capell-admin::table.layout'))
                ->sortable()
                ->limit(30)
                ->size('sm')
                ->visible(fn ($livewire): bool => empty($livewire->getTableFilterState('layout_id')['value']))
                ->toggleable(isToggledHiddenByDefault: true),
            TypeNameColumn::make('type.name'),
            Tables\Columns\TextColumn::make('tag.name')
                ->label(__('capell-admin::table.tag'))
                ->sortable()
                ->limit(30)
                ->visible(fn ($livewire): bool => empty($livewire->getTableFilterState('tag_id')['value']))
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\SpatieTagsColumn::make('tags')
                ->label(__('capell-admin::table.tags'))
                ->type(TagTypeEnum::PAGE->value)
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('assets')
                ->label(__('capell-admin::table.resources'))
                ->alignCenter()
                ->size('sm')
                ->disabledClick()
                ->view('capell-admin::components.tables.columns.page-assets')
                ->toggleable(isToggledHiddenByDefault: true),
            PageStatusColumn::make('status'),
            DateColumn::make('created_at')->toggleable(isToggledHiddenByDefault: true),
            DateColumn::make('updated_at')->toggleable(),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('layout_id')
                ->label(__('capell-admin::form.layout'))
                ->searchable()
                ->preload()
                ->relationship(
                    name: 'layout',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->enabled()->ordered()
                ),
            Tables\Filters\SelectFilter::make('type_id')
                ->label(__('capell-admin::form.page_type'))
                ->searchable()
                ->preload()
                ->relationship(
                    name: 'type',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->enabled()->pageType()
                ),
            Tables\Filters\Filter::make('filter')
                ->columnSpan(['default' => 1, 'md' => 2])
                ->columns(['default' => 1, 'md' => 2])
                ->form([
                    Forms\Components\Select::make('site_id')
                        ->label(__('capell-admin::form.site'))
                        ->reactive()
                        ->searchable()
                        ->options(fn (): array => CapellCore::getModel(ModelEnum::Site)::getOptions()->toArray()),

                    Forms\Components\Select::make('language_id')
                        ->label(__('capell-admin::table.language'))
                        ->searchable()
                        ->options(function (Forms\Get $get): array {
                            $site_id = $this->siteId !== null && $this->siteId !== 0 ? $this->siteId : $get('site_id');

                            /* @var class-string<\Capell\Core\Models\Language> $model */
                            $model = CapellCore::getModel(ModelEnum::Language);

                            return $model::when(
                                $site_id,
                                fn (Builder $query) => $query->whereHas(
                                    'sites',
                                    fn (BuilderContract $query) => $query->where('sites.id', $site_id)
                                )
                            )
                                ->ordered()
                                ->pluck('name', 'id')
                                ->toArray();
                        }),

                    Forms\Components\Select::make('parent_uuid')
                        ->label(__('capell-admin::form.parent_page'))
                        ->searchable()
                        ->options(function (Forms\Get $get): array {
                            $site_id = $this->siteId !== null && $this->siteId !== 0 ? $this->siteId : $get('site_id');

                            $pages = CapellCore::getModel(ModelEnum::Page)::with([
                                'site',
                                'ancestors',
                            ])
                                ->whereHas('children')
                                ->whereHas('type', fn (BuilderContract $query) => $query->enabled())
                                ->when($site_id, fn (Builder $query) => $query->where('site_id', $site_id))
                                ->when(
                                    $get('language_id'),
                                    fn (Builder $query) => $query->whereHas(
                                        'translations',
                                        fn (BuilderContract $query) => $query->where(
                                            'page_translations.language_id',
                                            $get('language_id')
                                        )
                                    )
                                )
                                ->orderBy('site_id')
                                ->orderBy('_lft');

                            $options = [];

                            $pages->each(function (Models\Page $page) use (&$options, $site_id): void {
                                $label = '';
                                if (! $site_id && $page->site) {
                                    $label .= $page->site->name.' » ';
                                }

                                if ($page->ancestors->isNotEmpty()) {
                                    $label .= $page->ancestors->pluck('name')
                                        ->map(fn ($item) => Str::limit($item, 30))
                                        ->implode(' » ')
                                        .' » ';
                                }

                                $label .= Str::limit($page->name, 40);

                                $options[$page->id] = $label;
                            });

                            return $options;
                        }),

                    Forms\Components\Select::make('tags')
                        ->label(__('capell-admin::form.tags'))
                        ->relationship(name: 'tags', titleAttribute: 'name', modifyQueryUsing: function (Builder $query, Forms\Get $get): Builder {
                            $languageId = $get('language_id');

                            if (! $languageId) {
                                return $query;
                            }

                            $code = CapellCore::getModel(ModelEnum::Language)::find($languageId, 'code')->code;

                            return $query->whereRaw('JSON_EXTRACT(`tags`.`name`, '.DB::getPdo()->quote('$.'.$code).') IS NOT NULL');
                        })
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(function (Models\Tag $record, Forms\Get $get): string {
                            if ($language_id = $get('language_id')) {
                                $code = CapellCore::getModel(ModelEnum::Language)::find($language_id, 'code')->code;

                                return $record->getTranslation('name', $code);
                            }

                            return $record->getTranslation('name', 'en');
                        }),

                    Forms\Components\CheckboxList::make('filter_groups')
                        ->label(__('capell-admin::form.page_type'))
                        ->default(['page'])
                        ->columns(3)
                        ->options(
                            function (): array {
                                /** @var class-string<Models\Type> $model */
                                $model = CapellCore::getModel(ModelEnum::Type);

                                return $model::pageType()
                                    ->whereNotNull('group')
                                    ->whereNot('group', 'Page')
                                    ->get()
                                    ->mapWithKeys(fn (Models\Type $type) => [$type->group => str($type->group)->plural()->title()])
                                    ->prepend(__('capell-admin::generic.pages'), 'Page')
                                    ->toArray();
                            }
                        ),
                ])
                ->query(function (Builder $query, array $data): void {
                    $query
                        ->when(
                            ! empty($data['language_id']),
                            fn (Builder $query) => $query->whereHas(
                                'translations',
                                fn (BuilderContract $query) => $query->where(
                                    'language_id',
                                    (int) $data['language_id']
                                )
                            )
                        )
                        ->when(
                            ! empty($data['tags']),
                            fn (Builder $query) => $query->whereHas(
                                'tags',
                                fn (BuilderContract $query) => $query->where('tags.id', (int) $data['tags'])
                            )
                        )
                        ->when(
                            ! empty($data['site_id']),
                            fn (Builder $query) => $query->where('site_id', (int) $data['site_id'])
                        )
                        ->when(
                            ! empty($data['parent_uuid']),
                            fn (Builder $query) => $query->where('parent_uuid', $data['parent_uuid'])
                        )
                        ->when(
                            $data['filter_groups'] ?? [],
                            fn (Builder $query, $groups) => $query->whereHas(
                                'type',
                                fn (BuilderContract $query) => $query->whereIn('group', $groups)
                                    ->when(
                                        in_array('page', $groups, true),
                                        fn (Builder $query) => $query->orWhereNull('group')
                                    )
                            )
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (! empty($data['site_id'])) {
                        /** @var class-string<Models\Site> $model */
                        $model = CapellCore::getModel(ModelEnum::Site);

                        $indicators['site_id'] = __(
                            'capell-admin::filter.site',
                            ['search' => $model::find($data['site_id'], 'name')?->name]
                        );
                    }

                    if (! empty($data['language_id'])) {
                        /** @var class-string<Models\Language> $model */
                        $model = CapellCore::getModel(ModelEnum::Language);

                        $indicators['language_id'] = __(
                            'capell-admin::filter.language',
                            ['search' => $model::find($data['language_id'], 'name')?->name]
                        );
                    }

                    if (! empty($data['parent_uuid'])) {
                        /** @var class-string<Models\Page> $model */
                        $model = CapellCore::getModel(ModelEnum::Page);

                        $indicators['parent_uuid'] = __(
                            'capell-admin::filter.parent',
                            [
                                'search' => $model::select('name')->firstWhere(
                                    'uuid',
                                    $data['parent_uuid']
                                )
                                    ?->name,
                            ]
                        );
                    }

                    if (! empty($data['tag_id'])) {
                        /** @var class-string<Models\Tag> $model */
                        $model = CapellCore::getModel(ModelEnum::Tag);

                        $indicators['tag_id'] = __(
                            'capell-admin::filter.tag',
                            ['search' => $model::find($data['tag_id'], 'name')?->name]
                        );
                    }

                    if (! empty($data['tags'])) {
                        /** @var class-string<Models\Tag> $model */
                        $model = CapellCore::getModel(ModelEnum::Tag);

                        $indicators['tags'] = __(
                            'capell-admin::filter.tag',
                            ['search' => $model::find($data['tags'], 'name')?->name]
                        );
                    }

                    if (! empty($data['filter_groups'])) {
                        $typeGroups = collect($data['filter_groups'])
                            ->map(fn ($group) => str($group)->plural()->title());

                        $indicators['filter_groups'] = __(
                            'capell-admin::filter.types_search',
                            ['search' => $typeGroups->implode(', ')]
                        );
                    }

                    return $indicators;
                }),

            DateFilter::make('publish_from')
                ->label(__('capell-admin::form.publish_date')),

            DraftFilter::make(),
        ];
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
            ->when($this->pageId, fn (BuilderContract $query) => $query->whereKeyNot($this->pageId));
    }
}
