<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\ContentResource\RelationManagers;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Tables\Columns\CuratorColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Core\Actions\EditPageUrlAction;
use Capell\Core\Enums\TypeEnum;
use Capell\Layout\Filament\Concerns\HasAssetsRelationManager;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\ContentAsset;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
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
        return __('capell-admin::tab.resources');
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema(static::getResourceableForm());
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withAssets())
            ->description(__('capell-admin::generic.content_assets_description'))
            ->columns([
                Tables\Columns\TextColumn::make('asset_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(
                        query: fn (Builder $query, string $search): Builder => $query->where('asset_id', $search),
                    ),
                NameColumn::make('asset.name'),
                CuratorColumn::make('asset.image')
                    ->label(__('capell-admin::table.image'))
                    ->relationship('asset.image')
                    ->extraHeaderAttributes(['style' => 'width:1%']),
                Tables\Columns\TextColumn::make('asset_type')
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
                Tables\Filters\Filter::make('filter')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->label(__('capell-admin::form.type'))
                            ->reactive()
                            ->options(fn (): array => ContentAsset::getTypes()),
                    ]),
                Tables\Filters\SelectFilter::make('type_id')
                    ->label(__('capell-admin::form.type'))
                    ->options(fn (): array => Content::getTypes()),
            ])
            ->headerActions([
                self::createResourcesAction(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
