<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\RelationManagers;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Core\Actions\EditPageUrlAction;
use Capell\Core\Data\AssetData;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Filament\Concerns\HasAssetsRelationManager;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\ContentAsset;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContentAssetsRelationManager extends RelationManager
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withAssets())
            ->description(__('capell-admin::generic.content_assets_description'))
            ->columns([
                TextColumn::make('asset_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->where('asset_id', $search),
                    ),
                NameColumn::make('asset.name'),
                SpatieMediaLibraryImageColumn::make('asset.image')
                    ->label(__('capell-admin::table.image'))
                    ->collection('image')
                    ->width(0),
                TextColumn::make('asset_type')
                    ->badge(),
            ])
            ->recordUrl(
                fn (ContentAsset $record): ?string => match ($record->asset_type) {
                    TypeEnum::Page->value => EditPageUrlAction::run($record->asset),
                    default => CapellAdmin::getResource(ucfirst($record->asset_type))::getUrl(
                        'edit',
                        ['record' => $record->asset]
                    ),
                }
            )
            ->filters([
                SelectFilter::make('asset_type')
                    ->label(__('capell-admin::form.asset_type'))
                    ->options(
                        fn (): array => CapellCore::getAssets()
                            ->mapWithKeys(
                                static fn (AssetData $asset): array => [$asset->getKey() => $asset->getLabel()]
                            )
                            ->toArray()
                    ),
                SelectFilter::make('type_id')
                    ->label(__('capell-admin::form.type'))
                    ->options(fn (): array => Content::getTypes()),
            ])
            ->headerActions([
                self::createResourcesAction(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
