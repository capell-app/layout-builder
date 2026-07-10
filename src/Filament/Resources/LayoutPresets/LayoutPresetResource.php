<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\LayoutPresets;

use BackedEnum;
use Capell\LayoutBuilder\Filament\Resources\LayoutPresets\Pages\ListLayoutPresets;
use Capell\LayoutBuilder\Models\LayoutPreset;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

final class LayoutPresetResource extends Resource
{
    protected static ?string $slug = 'layout-builder/presets';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    #[Override]
    public static function getModel(): string
    {
        return LayoutPreset::class;
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('mode')->badge(),
                Tables\Columns\TextColumn::make('category')->toggleable(),
                Tables\Columns\TextColumn::make('revision')->label(__('capell-layout-builder::table.revision'))->sortable(),
                Tables\Columns\TextColumn::make('usages_count')->counts('usages')->label(__('capell-layout-builder::table.usage_count')),
                Tables\Columns\TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('site');
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return __('capell-layout-builder::navigation.layout_presets');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_websites');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('capell-layout-builder::navigation.layout_presets');
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return __('capell-layout-builder::navigation.layout_preset');
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListLayoutPresets::route('/'),
        ];
    }
}
