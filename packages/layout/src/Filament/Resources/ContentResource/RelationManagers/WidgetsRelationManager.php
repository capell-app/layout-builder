<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\ContentResource\RelationManagers;

use Awcodes\BadgeableColumn\Components\Badge;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Admin\Filament\Concerns\HideEmptyRelationManager;
use Capell\Layout\Filament\Resources\WidgetResource;
use Capell\Layout\Models\Widget;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WidgetsRelationManager extends RelationManager
{
    use HasRelationManagerBadge;
    use HideEmptyRelationManager;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $relationship = 'widgets';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::tab.widgets');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with([
                    'creator',
                    'editor',
                    'type',
                ])
            )
            ->columns([
                IdentifierColumn::make('id'),
                NameColumn::make('name')
                    ->icon(fn ($record) => $record->type->admin['icon'] ?? '')
                    ->suffixBadges([
                        Badge::make('type.name')
                            ->label(fn (Widget $record) => $record->type?->name)
                            ->color('gray'),
                    ]),
                TextColumn::make('key')
                    ->label(__('capell-admin::table.key'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (Widget $record): string => WidgetResource::getUrl('edit', ['record' => $record]));
    }
}
