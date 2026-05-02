<?php

declare(strict_types=1);

namespace Capell\Navigation\Filament\Resources\Sites\RelationManagers;

use BackedEnum;
use Capell\Admin\Filament\Components\Tables\Columns\LanguageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Navigation\Filament\Components\Tables\Columns\Navigation\NavigationItemsColumn;
use Capell\Navigation\Filament\Resources\Navigations\NavigationResource;
use Capell\Navigation\Models\Navigation;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Override;

class NavigationsRelationManager extends RelationManager
{
    use HasRelationManagerBadge;

    protected static string|BackedEnum|null $icon = 'heroicon-o-globe-alt';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $relationship = 'navigations';

    public static function getLabel(): ?string
    {
        return __('capell-admin::generic.navigations');
    }

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::tab.navigations');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with(['language']),
            )
            ->description(__('capell-admin::generic.site_navigations_description'))
            ->columns([
                NameColumn::make('name')
                    ->description(fn (Navigation $record): string => $record->key)
                    ->searchable(['id', 'name', 'key']),
                NavigationItemsColumn::make('items'),
                LanguageColumn::make('language'),
            ])
            ->filters([
                SelectFilter::make('language_id')
                    ->label(__('capell-admin::form.language'))
                    ->relationship('language', 'name', modifyQueryUsing: fn (Builder $query) => $query->ordered()),
            ])
            ->recordActions([
                Action::make('navigation')
                    ->label(__('capell-admin::button.edit'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Navigation $record): string => NavigationResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    #[Override]
    protected static function getPluralModelLabel(): string
    {
        return __('capell-admin::generic.navigations');
    }
}
