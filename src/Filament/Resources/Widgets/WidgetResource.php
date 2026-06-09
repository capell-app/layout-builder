<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Filament\Resources\Widgets;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\LayoutBuilder\Enums\ConfiguratorTypeEnum;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\CreateWidget;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\EditWidget;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Pages\ListWidgets;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Schemas\WidgetForm;
use Capell\LayoutBuilder\Filament\Resources\Widgets\Tables\WidgetsTable;
use Capell\LayoutBuilder\Models\Widget;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Override;

class WidgetResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;
    use HasNavigationBadge;

    protected static ?string $slug = 'layout-builder/widgets';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = true;

    protected static string $formConfigurator = WidgetForm::class;

    protected static string $tableConfigurator = WidgetsTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::PuzzlePiece;

    protected static ?int $navigationSort = 2;

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
        return ConfiguratorTypeEnum::Widget;
    }

    /**
     * @return class-string<Widget>
     */
    #[Override]
    public static function getModel(): string
    {
        return Widget::class;
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-layout-builder::navigation.widgets'));
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    #[Override]
    public static function getNavigationParentItem(): ?string
    {
        return (string) __('capell-admin::navigation.website');
    }

    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('capell-layout-builder::navigation.widgets');
    }

    #[Override]
    public static function getModelLabel(): string
    {
        return __('capell-layout-builder::navigation.widget');
    }

    #[Override]
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-layout-builder.resources.widget.icon', static::$navigationIcon);
    }

    #[Override]
    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-layout-builder.resources.widget.active_icon', static::$activeNavigationIcon);
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
     * @param  Model&Widget  $record
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
    public static function getPages(): array
    {
        return [
            'index' => ListWidgets::route('/'),
            'edit' => EditWidget::route('/{record}/edit'),
            'create' => CreateWidget::route('/create'),
        ];
    }
}
