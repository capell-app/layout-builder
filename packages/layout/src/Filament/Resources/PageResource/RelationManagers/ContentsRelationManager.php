<?php

declare(strict_types=1);

namespace Capell\Layout\Filament\Resources\PageResource\RelationManagers;

use Capell\Admin\Filament\Components\Tables\Actions\EditAction;
use Capell\Admin\Filament\Components\Tables\Actions\ReplicateAction;
use Capell\Admin\Filament\Components\Tables\Columns\CuratorColumn;
use Capell\Admin\Filament\Components\Tables\Columns\IdentifierColumn;
use Capell\Admin\Filament\Components\Tables\Columns\LanguagesColumn;
use Capell\Admin\Filament\Components\Tables\Columns\TypeNameColumn;
use Capell\Admin\Filament\Concerns\HasRelationManagerBadge;
use Capell\Core\Enums\TagTypeEnum;
use Capell\Layout\Filament\Components\Tables\Columns\Content\ContentNameColumn;
use Capell\Layout\Filament\Resources\ContentResource;
use Capell\Layout\Models\Content;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
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

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema(ContentResource::getFormSchema($form));
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
                Tables\Columns\SpatieTagsColumn::make('tags')
                    ->label(__('capell-admin::table.tags'))
                    ->type(TagTypeEnum::CONTENT->value)
                    ->toggleable(isToggledHiddenByDefault: true),
                CuratorColumn::make('meta.image')
                    ->relationship('image')
                    ->toggleable(),
            ])
            ->filters(ContentResource::getTableFilters())
            ->recordClasses(fn (Content $record): ?string => match (true) {
                (bool) $record->deleted_at => 'table-row-warning',
                default => null,
            })
            ->actions([
                EditAction::make(),
                Tables\Actions\ActionGroup::make([
                    ReplicateAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
    }
}
