<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Contents\RelationManagers;

use BackedEnum;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\MediaLibraryImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\Page\PageNameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Concerns\HideEmptyRelationManager;
use Capell\Admin\Filament\RelationManagers\AbstractPagesRelationManager;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Actions\GetEditPageResourceUrlAction;
use Capell\Core\Enums\AssetEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Layout\Models\Content;
use Capell\Layout\Models\WidgetAsset;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Override;

/**
 * @property Content $ownerRecord
 */
class PagesRelationManager extends AbstractPagesRelationManager
{
    use HideEmptyRelationManager;

    protected static string $relationship = 'pages';

    #[Override]
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::tab.pages');
    }

    #[Override]
    public static function getIcon(Model $ownerRecord, string $pageClass): null|string|BackedEnum
    {
        if ($pageClass instanceof PageResource) {
            return $pageClass::getNavigationIcon();
        }

        return CapellCore::getAsset(AssetEnum::Page)->getIcon();
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with([
                    'pageable' => [
                        'ancestors.type',
                        'editor',
                        'image',
                        'type',
                        'pageUrl.siteDomain',
                    ],
                ]),
            )
            ->description(fn (self $livewire, Table $table): ?string => $livewire->getDescription($table))
            ->columns([
                IdentifierColumn::make('pageable.id'),
                PageNameColumn::make('pageable.name')
                    ->resolveRecordKey('pageable_id')
                    ->wrap()
                    ->withParents()
                    ->withTypeIcon()
                    ->withUrl(),
                SiteColumn::make('pageable.site.name'),
                MediaLibraryImageColumn::make('pageable.image')
                    ->collection('image'),
                DateColumn::make('pageable.updated_at')
                    ->sortable(false),
            ])
            ->recordUrl(fn (WidgetAsset $record): ?string => GetEditPageResourceUrlAction::run($record->pageable))
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->url(fn (WidgetAsset $record): ?string => GetEditPageResourceUrlAction::run($record->pageable))
                    ->tooltip(fn (EditAction $action): ?string => $action->getLabel()),
            ]);
    }

    /**
     * @param  Model | array<string, mixed>  $record
     */
    public function getTableRecordKey(Model|array $record): string
    {
        return $record->pageable_type . '-' . $record->pageable_id;
    }

    protected static function modifyBadgeQueryUsing(Relation $query): Relation
    {
        return $query->distinct(['widget_assets.pageable_type', 'widget_assets.pageable_id'])
            ->whereNotNull('widget_assets.pageable_type')
            ->whereNotNull('widget_assets.pageable_id');
    }

    protected function getDescription(Table $table): ?string
    {
        return __('capell-admin::generic.content_pages_info', ['total' => $table->getQuery()->count()]);
    }
}
