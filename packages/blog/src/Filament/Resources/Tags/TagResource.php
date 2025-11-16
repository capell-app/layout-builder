<?php

declare(strict_types=1);

namespace Capell\Blog\Filament\Resources\Tags;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasFormConfigurator;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Admin\Filament\Concerns\HasTableConfigurator;
use Capell\Admin\Filament\Contracts\FormConfigurator;
use Capell\Admin\Filament\Contracts\TableConfigurator;
use Capell\Blog\Enums\BlogModelEnum;
use Capell\Blog\Filament\Resources\Tags\Pages\CreateTag;
use Capell\Blog\Filament\Resources\Tags\Pages\EditTag;
use Capell\Blog\Filament\Resources\Tags\Pages\ListTags;
use Capell\Blog\Filament\Resources\Tags\RelationManagers\PagesRelationManager;
use Capell\Blog\Filament\Resources\Tags\Schemas\TagForm;
use Capell\Blog\Filament\Resources\Tags\Tables\TagsTable;
use Capell\Blog\Models\Tag;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Language;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Override;

class TagResource extends Resource
{
    use HasFormConfigurator;
    use HasNavigationBadge;
    use HasTableConfigurator;
    use Translatable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'name';

    /** @var class-string<FormConfigurator> */
    protected static string $formConfigurator = TagForm::class;

    /** @var class-string<TableConfigurator> */
    protected static string $tableConfigurator = TagsTable::class;

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return static::getFormConfigurator()::configure($schema);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    /**
     * @return class-string<Tag>
     */
    #[Override]
    public static function getModel(): string
    {
        return CapellCore::getModel(BlogModelEnum::Tag);
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_assets'));
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-admin::navigation.tags'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-admin::generic.tags');
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            PagesRelationManager::class,
        ];
    }

    public static function getTranslatableLocales(): array
    {
        return Language::getLanguageLocales();
    }
}
