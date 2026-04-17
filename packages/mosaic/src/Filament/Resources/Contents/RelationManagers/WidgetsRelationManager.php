<?php

declare(strict_types=1);

namespace Capell\Mosaic\Filament\Resources\Contents\RelationManagers;

use Awcodes\BadgeableColumn\Components\Badge;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Admin\Filament\Concerns\HideEmptyRelationManager;
use Capell\Mosaic\Filament\Resources\Widgets\WidgetResource;
use Capell\Mosaic\Models\WidgetAsset;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class WidgetsRelationManager extends RelationManager
{
    use HasRelationManagerBadge;
    use HideEmptyRelationManager;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $relationship = 'widgets';

    protected static string $badgeRelationship = 'widgets';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-mosaic::tab.widgets');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with([
                    'widget' => [
                        'creator',
                        'editor',
                        'type',
                    ],
                ]),
            )
            ->columns([
                IdentifierColumn::make('widget.id'),
                NameColumn::make('widget.name')
                    ->icon(fn (WidgetAsset $record): string => $record->type->admin['icon'] ?? '')
                    ->suffixBadges([
                        Badge::make('widget.type.name')
                            ->label(fn (WidgetAsset $record): string => $record->widget?->type?->name ?? '')
                            ->color('gray'),
                    ]),
                TextColumn::make('widget.key')
                    ->label(__('capell-admin::table.key'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(fn (WidgetAsset $record): string => WidgetResource::getUrl('edit', ['record' => $record->widget]));
    }

    /**
     * @param  Model | array<string, mixed>  $record
     */
    public function getTableRecordKey(Model|array $record): string
    {
        return (string) $record->widget_id;
    }

    protected static function modifyBadgeQueryUsing(Relation $query): Relation
    {
        return $query->distinct('widget_assets.widget_id');
    }
}
