<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Elements;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Elements\Pages\CreateElement;
use Capell\LayoutBuilder\Filament\Resources\Elements\Pages\EditElement;
use Capell\LayoutBuilder\Filament\Resources\Elements\Pages\ListElements;
use Capell\LayoutBuilder\Filament\Resources\Elements\RelationManagers\LayoutsRelationManager;
use Capell\LayoutBuilder\Filament\Resources\Elements\Schemas\ElementForm;
use Capell\LayoutBuilder\Filament\Resources\Elements\Tables\ElementsTable;
use Capell\LayoutBuilder\Models\Element;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;

class ElementResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;
    use HasNavigationBadge;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = ElementForm::class;

    protected static string $tableConfigurator = ElementsTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Cube;

    protected static ?int $navigationSort = 5;

    #[Override]
    public static function form(Schema $configurator): Schema
    {
        return static::getFormConfigurator()::configure($configurator);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    public static function getResourceType(): ConfiguratorTypeEnum
    {
        return ConfiguratorTypeEnum::Element;
    }

    /**
     * @return class-string<Element>
     */
    #[Override]
    public static function getModel(): string
    {
        return Element::class;
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-layout-builder::navigation.elements'));
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_layouts'));
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('capell-layout-builder::navigation.elements');
    }

    #[Override]
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-layout-builder.resources.element.icon', static::$navigationIcon);
    }

    #[Override]
    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-layout-builder.resources.element.active_icon', static::$activeNavigationIcon);
    }

    #[Override]
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    #[Override]
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'key', 'translations.title', 'component', 'view_file'];
    }

    /**
     * @param  Model&Element  $record
     * @return array|string[]
     */
    #[Override]
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->title !== null && $record->title !== '') {
            $details[__('capell-admin::generic.title')] = $record->title;
        }

        return $details;
    }

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            LayoutsRelationManager::class,
        ];
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListElements::route('/'),
            'edit' => EditElement::route('/{record}/edit'),
            'create' => CreateElement::route('/create'),
        ];
    }
}
