<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Concerns;

use Capell\Core\Data\AssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Page;
use Capell\Layout\Filament\Components\Forms\AssetTypeToggleButtons;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\NestedSet;

/**
 * @mixin RelationManager
 */
trait HasAssetsRelationManager
{
    protected static function createResourcesAction(): Action
    {
        return CreateAction::make()
            ->label(__('capell-admin::button.add_asset'))
            ->color('primary')
            ->successNotificationTitle(__('capell-admin::messsage.asset_added'))
            ->using(function (array $data, self $livewire): Model {
                foreach ($data['assets'] as $uuid) {
                    $livewire->ownerRecord->assets()->create([
                        'asset_id' => $uuid,
                        'asset_type' => $data['asset_type'],
                    ]);
                }

                return $livewire->ownerRecord;
            });
    }

    protected static function getAssetForm(): array
    {
        return [
            AssetTypeToggleButtons::make('asset_type')
                ->required()
                ->reactive(),
            Select::make('assets')
                ->label(
                    fn (Get $get): string => $get('asset_type')
                        ? __('capell-layout::form.select_add_type', ['type' => $get('asset_type')])
                        : __('capell-layout::form.select_add_asset_type')
                )
                ->required()
                ->searchable()
                ->multiple()
                ->disabled(fn (Get $get): bool => ! $get('asset_type'))
                ->getSearchResultsUsing(
                    static fn (Select $component, Get $get, self $livewire, string $search): array => self::getAssetOptions(
                        $component,
                        $livewire->ownerRecord,
                        $get('asset_type'),
                        limit: $component->getOptionsLimit(),
                        search: $search
                    )
                )
                ->options(
                    fn (Select $component, Get $get, self $livewire): array => self::getAssetOptions(
                        $component,
                        $livewire->ownerRecord,
                        $get('asset_type'),
                        limit: $component->getOptionsLimit()
                    )
                ),
        ];
    }

    protected static function getAssetOptionsFromResults($results, AssetData $asset): Collection
    {
        if ($asset->name === 'Page') {
            return self::getPageAssetOptions($results);
        }

        return $results->pluck('name', 'id');
    }

    protected static function getPageAssetOptions($results): Collection
    {
        $options = collect();

        $results->each(function (Page $page) use (&$options): void {
            $label = $page->site->name.' » ';

            $ancestors = $page->ancestors()->get();

            if ($ancestors->isNotEmpty()) {
                $label .= $ancestors->pluck('name')
                    ->map(fn ($item) => Str::limit($item, 30))
                    ->implode(' » ')
                    .' » ';
            }

            $label .= Str::limit($page->name, 40);

            $options->put($page->id, $label);
        });

        return $options;
    }

    private static function getAssetOptions(Select $component, Model $record, ?string $type, int $limit = 10, ?string $search = null): array
    {
        if ($type === null || $type === '' || $type === '0') {
            return [];
        }

        $asset = CapellCore::getAsset($type);

        /* @var class-string<Model> $model */
        $model = $asset->model;

        $query = $model::query()
            ->select([
                'id',
                'id',
                'name',
            ])
            ->whereKeyNot($record->id)
            ->whereNotExists(
                fn (BuilderContract $query) => $query
                    ->from('content_assets')
                    ->where('content_assets.content_id', $record->id)
                    ->whereColumn('content_assets.asset_id', app($model)->qualifyColumn('id'))
                    ->where('asset_type', $type)
            )
            ->when(
                $asset->name === 'Page',
                fn (BuilderContract $query) => $query->with([
                    'ancestors' => fn (Relation $query) => $query->withDrafts(),
                    'site',
                ])
                    ->addSelect([
                        'pages.site_id',
                        'pages.parent_id',
                        'pages._lft',
                        'pages._rgt',
                    ])
                    ->withDrafts()
                    ->orderBy('site_id')
                    ->orderBy(NestedSet::LFT, 'DESC')
                    ->whereHas(
                        'type',
                        fn (Builder $query) => $query->where(
                            fn (Builder $query) => $query->whereJsonDoesntContain(
                                'admin->exclude_from_selection',
                                true
                            )
                                ->orWhereNull('admin')
                        )
                            ->where(
                                fn (Builder $query) => $query->where('group', '!=', 'system')
                                    ->orWhereNull('group')
                            )
                    )
            )
            ->when(
                $search,
                fn (Builder $query, string $search): Builder => $query->where(
                    'name',
                    'like',
                    sprintf('%%%s%%', $search)
                )
            );

        $total = $query->count();

        $results = $query->limit($limit)->get();

        $options = self::getAssetOptionsFromResults($results, $asset);

        if ($total > $limit) {
            $options->pop();
            $options->put(null, __('capell-admin::form.more_results', ['count' => $total - $limit]));
            $component->disableOptionWhen(fn (string $value): bool => ! $value);
        }

        return $options->toArray();
    }
}
