<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Concerns;

use Capell\Core\Enums\TypeEnum;
use Capell\Core\Facades\CapellCore;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin RelationManager
 */
trait HasAssetsRelationManager
{
    protected static function createResourcesAction(): Tables\Actions\Action
    {
        return Tables\Actions\CreateAction::make()
            ->label(__('capell-admin::button.add_resource'))
            ->color('primary')
            ->successNotificationTitle(__('capell-admin::notification.resource_added'))
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

    protected static function getResourceableForm(): array
    {
        return [
            Forms\Components\ToggleButtons::make('asset_type')
                ->label(__('capell-admin::form.resource_type'))
                ->required()
                ->options(TypeEnum::getResourceTypes())
                ->inline()
                ->reactive(),
            Forms\Components\Select::make('assets')
                ->label(__('capell-admin::form.resources'))
                ->required()
                ->searchable()
                ->multiple()
                ->visible(fn (Get $get): bool => (bool) $get('asset_type'))
                ->getSearchResultsUsing(
                    static fn (Get $get, self $livewire, string $search): array => self::getAssetOptions(
                        $livewire->ownerRecord,
                        $get('asset_type'),
                        search: $search
                    )
                )
                ->options(
                    fn (Get $get, self $livewire): array => self::getAssetOptions(
                        $livewire->ownerRecord,
                        $get('asset_type'))
                ),
        ];
    }

    private static function getAssetOptions(Model $record, ?string $type, ?string $search = null): array
    {
        if ($type === null || $type === '' || $type === '0') {
            return [];
        }

        $asset = CapellCore::getAsset($type);

        /* @var class-string<Model> $model */
        $model = $asset->model;

        return $model::query()
            ->whereKeyNot($record->id)
            ->whereNotExists(
                fn (BuilderContract $query) => $query
                    ->from('content_assets')
                    ->where('content_assets.content_id', $record->id)
                    ->whereColumn('content_assets.asset_id', app($model)->qualifyColumn('id'))
                    ->where('asset_type', $type)
            )
            ->when(
                $search,
                fn (Builder $query, string $search): Builder => $query->where(
                    'name',
                    'like',
                    sprintf('%%%s%%', $search)
                )
            )
            ->limit(100)
            ->pluck('name', 'uuid')
            ->toArray();
    }
}
