<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Collections\RelationManagers;

use Capell\Admin\Actions\GetAssetResourceUrlAction;
use Capell\Admin\Filament\Components\Tables\Columns\MediaLibraryImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Core\Data\AssetData;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\AssetRelation;
use Capell\Layout\Filament\Concerns\HasAssetsRelationManager;
use Capell\Layout\Models\Collection;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CollectionAssetsRelationManager extends RelationManager
{
    use HasAssetsRelationManager;
    use HasRelationManagerBadge;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $relationship = 'assets';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::tab.assets');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(static::getAssetForm())->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'asset' => fn (MorphTo $morphTo) => $morphTo->morphWith(
                    CapellCore::getAssets()
                        ->mapWithKeys(fn (AssetData $asset): array => [
                            $asset->model => method_exists($asset->model, 'getMorphRelations')
                                ? $asset->model::getMorphRelations()
                                : [],
                        ])
                        ->toArray(),
                ),
            ]))
            ->description(__('capell-admin::generic.content_assets_description'))
            ->columns([
                TextColumn::make('asset_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->where('asset_id', $search),
                    ),
                NameColumn::make('asset.name'),
                MediaLibraryImageColumn::make('asset.image')
                    ->label(__('capell-admin::table.image'))
                    ->collection('image')
                    ->autoEagerLoadRelation(false),
                TextColumn::make('asset_type')
                    ->label(__('capell-admin::table.type'))
                    ->width(0)
                    ->badge(),
            ])
            ->recordUrl(
                fn (AssetRelation $record): string => GetAssetResourceUrlAction::run($record->asset_type, $record->asset),
            )
            ->filters([
                SelectFilter::make('asset_type')
                    ->label(__('capell-admin::form.asset_type'))
                    ->options(
                        fn (): array => CapellCore::getAssets()
                            ->mapWithKeys(
                                static fn (AssetData $asset): array => [$asset->getKey() => $asset->getLabel()],
                            )
                            ->all(),
                    ),
                SelectFilter::make('type_id')
                    ->label(__('capell-admin::form.type'))
                    ->options(fn (): array => Collection::getTypes()),
            ])
            ->headerActions([
                self::createResourcesAction(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }
}
