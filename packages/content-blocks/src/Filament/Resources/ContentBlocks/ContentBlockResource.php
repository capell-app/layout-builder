<?php

declare(strict_types=1);

namespace Capell\ContentBlocks\Filament\Resources\ContentBlocks;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\ContentBlocks\Enums\ConfiguratorTypeEnum;
use Capell\ContentBlocks\Enums\LayoutTypeEnum;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\Pages\CreateContentBlock;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\Pages\EditContentBlock;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\Pages\ListContentBlocks;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\RelationManagers\ContentBlockAssetsRelationManager;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\Schemas\ContentBlockForm;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\Tables\ContentBlocksTable;
use Capell\ContentBlocks\Filament\Resources\ContentBlocks\Widgets\ContentBlockAlertsWidget;
use Capell\ContentBlocks\Models\ContentBlock;
use Capell\ContentBlocks\Providers\ContentBlocksServiceProvider;
use Capell\Core\Facades\CapellCore;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class ContentBlockResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;
    use HasNavigationBadge;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = ContentBlockForm::class;

    protected static string $tableConfigurator = ContentBlocksTable::class;

    public static function form(Schema $configurator): Schema
    {
        return static::getFormConfigurator()::configure($configurator);
    }

    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return CapellCore::getPackage(ContentBlocksServiceProvider::$packageName)->isInstalled();
    }

    public static function getResourceType(): ConfiguratorTypeEnum
    {
        return ConfiguratorTypeEnum::ContentBlock;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'translations.title'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with([
                'site:id,name,default',
                'type:id,name',
                'ancestors',
            ]);
    }

    /**
     * @param  ContentBlock  $record
     * @return array|string[]
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->title !== $record->name) {
            $details[] = $record->title;
        }

        if (($breadcrumb = self::buildGlobalSearchBreadcrumbs($record)) instanceof HtmlString) {
            $details[] = $breadcrumb;
        }

        return $details;
    }

    /**
     * @return class-string<ContentBlock>
     */
    public static function getModel(): string
    {
        return ContentBlock::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_content'));
    }

    public static function getNavigationLabel(): string
    {
        return (string) (__('capell-content-blocks::navigation.content_blocks'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentBlocks::route('/'),
            'create' => CreateContentBlock::route('/create'),
            'edit' => EditContentBlock::route('/{record}/edit'),
        ];
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return CapellCore::getAsset(LayoutTypeEnum::ContentBlock->name)->getIcon();
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return CapellCore::getAsset(LayoutTypeEnum::ContentBlock->name)->getActiveIcon();
    }

    public static function getModelLabel(): string
    {
        return __('capell-content-blocks::generic.content_block');
    }

    public static function getPluralModelLabel(): string
    {
        return __('capell-content-blocks::generic.content_blocks');
    }

    public static function getRelations(): array
    {
        return [
            ContentBlockAssetsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ContentBlockAlertsWidget::class,
        ];
    }

    private static function buildGlobalSearchBreadcrumbs(ContentBlock $record): ?HtmlString
    {
        $breadcrumbs = [];

        if ($record->site !== null && ! $record->site->default) {
            $breadcrumbs[] = $record->site->name;
        }

        if ($record->ancestors->isNotEmpty()) {
            $breadcrumbs[] = $record->ancestors->pluck('name')->implode(' &raquo; ');
        }

        if (filled($breadcrumbs)) {
            return new HtmlString(implode(' &raquo; ', $breadcrumbs));
        }

        return null;
    }
}
