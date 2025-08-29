<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\Pages\RelationManagers;

use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\TypeNameColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Core\Enums\TagTypeEnum;
use Capell\Layout\Filament\Components\Tables\Columns\Content\ContentNameColumn;
use Capell\Layout\Filament\Resources\Contents\ContentResource;
use Capell\Layout\Filament\Resources\Contents\Schemas\ContentForm;
use Capell\Layout\Models\Content;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\SpatieTagsColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContentsRelationManager extends RelationManager
{
    use HasRelationManagerBadge;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $relationship = 'contents';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('capell-layout::tab.contents');
    }

    public function form(Schema $schema): Schema
    {
        return ContentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table->modifyQueryUsing(
            fn (Builder $query): Builder => $query->with([
                'ancestors',
                'translations.language',
                'image',
                'type',
            ])
        )
            ->description(__('Contents related to this page'))
            ->columns([
                IdentifierColumn::make('id'),
                ContentNameColumn::make('name'),
                TextColumn::make('translation.title')
                    ->label(__('capell-admin::table.title'))
                    ->searchable()
                    ->html()
                    ->toggleable(isToggledHiddenByDefault: true),
                LanguagesColumn::make('translations.language'),
                TextColumn::make('parent.name')
                    ->label(__('capell-admin::table.parent'))
                    ->searchable()
                    ->sortable()
                    ->limit(60)
                    ->linkRecord()
                    ->toggleable(isToggledHiddenByDefault: true),
                TypeNameColumn::make('type.name'),
                SpatieTagsColumn::make('tags')
                    ->label(__('capell-admin::table.tags'))
                    ->type(TagTypeEnum::CONTENT->value)
                    ->toggleable(isToggledHiddenByDefault: true),
                SpatieMediaLibraryImageColumn::make('image')
                    ->label(__('capell-admin::table.image'))
                    ->collection('image')
                    ->toggleable(),
            ])
            ->filters(ContentResource::getTableFilters())
            ->recordClasses(fn (Content $record): ?string => match (true) {
                (bool) $record->deleted_at => 'table-row-warning',
                default => null,
            })
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    ReplicateAction::make(),
                    DeleteAction::make(),
                ])
                    ->color('gray'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]);
    }
}
