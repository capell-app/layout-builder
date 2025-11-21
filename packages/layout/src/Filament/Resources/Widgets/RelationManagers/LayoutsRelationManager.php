<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Widgets\RelationManagers;

use Capell\Admin\Enums\ResourceEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Components\Tables\Columns\DateColumn;
use Capell\Admin\Filament\Components\Tables\Columns\ImageColumn;
use Capell\Admin\Filament\Components\Tables\Columns\NameColumn;
use Capell\Admin\Filament\Components\Tables\Columns\SiteColumn;
use Capell\Admin\Filament\Components\Tables\Columns\StatusIconColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Core\Models\Layout;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class LayoutsRelationManager extends RelationManager
{
    use HasRelationManagerBadge;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $relationship = 'layouts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-admin::generic.layouts');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(
                fn (Builder $query): Builder => $query->with([
                    'creator',
                    'editor',
                    'image',
                ])
                    ->withCount('pages'),
            )
            ->description(__('capell-admin::generic.widget_layouts_info'))
            ->columns([
                NameColumn::make('name')
                    ->defaultBadge(),
                ImageColumn::make('admin.image')
                    ->visibility('public')
                    ->toggleable(),
                SiteColumn::make('site.name'),
                TextColumn::make('theme.name')
                    ->label(__('capell-admin::table.theme'))
                    ->sortable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('pages_count')
                    ->label(__('capell-admin::table.total_pages'))
                    ->sortable()
                    ->alignCenter()
                    ->disabledClick()
                    ->toggleable()
                    ->formatStateUsing(
                        fn (Layout $record, $state): HtmlString => new HtmlString(Blade::render('capell-admin::components.tables.url', [
                            'state' => $state,
                            'url' => PageResource::getUrl('index', ['tableFilters[layout_id][value]' => $record->id]),
                        ])),
                    ),
                StatusIconColumn::make('status'),
                DateColumn::make('created_at'),
                DateColumn::make('updated_at'),
            ])
            ->filters([
                SelectFilter::make('site_id')
                    ->label(__('capell-admin::form.site'))
                    ->relationship(
                        name: 'site',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->ordered(),
                    ),
            ])
            ->recordUrl(
                fn (Layout $record): string => CapellAdmin::getResource(ResourceEnum::Layout)::getUrl('edit', ['record' => $record]),
            );
    }
}
