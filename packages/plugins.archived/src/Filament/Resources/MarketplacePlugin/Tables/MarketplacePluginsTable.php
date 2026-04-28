<?php

declare(strict_types=1);

namespace Capell\Plugins\Filament\Resources\MarketplacePlugin\Tables;

use Capell\Plugins\Enums\LicenseModel;
use Capell\Plugins\Enums\PluginKind;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MarketplacePluginsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('id')
                    ->label(__('ID'))
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label(__('capell-plugins::table.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vendor')
                    ->label(__('capell-plugins::table.vendor'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kind')
                    ->label(__('capell-plugins::table.kind'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('license_model')
                    ->label(__('capell-plugins::table.license_model'))
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_visible')
                    ->label(__('capell-plugins::table.visible'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('capell-plugins::table.slug'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('capell-plugins::table.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('capell-plugins::table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label(__('capell-plugins::table.kind'))
                    ->options(self::getKindOptions()),
                SelectFilter::make('license_model')
                    ->label(__('capell-plugins::table.license_model'))
                    ->options(self::getLicenseModelOptions()),
                TernaryFilter::make('is_visible')
                    ->label(__('capell-plugins::table.visible')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function getKindOptions(): array
    {
        $options = [];
        foreach (PluginKind::cases() as $case) {
            $options[$case->value] = ucfirst(str_replace('_', ' ', $case->value));
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private static function getLicenseModelOptions(): array
    {
        $options = [];
        foreach (LicenseModel::cases() as $case) {
            $options[$case->value] = ucfirst(str_replace('_', ' ', $case->value));
        }

        return $options;
    }
}
