<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Filament\Resources\PublishingStudio;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\PublishingStudio\Enums\WorkspaceStatusEnum;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Pages\CompareVersionPage;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Pages\ManagePublishingStudio;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Schemas\WorkspaceForm;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Tables\PublishingStudioTable;
use Capell\PublishingStudio\Models\Workspace;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Override;

class WorkspaceResource extends Resource
{
    use HasConfiguredForm;
    use HasConfiguredTable;
    use HasNavigationBadge;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string $formConfigurator = WorkspaceForm::class;

    protected static string $tableConfigurator = PublishingStudioTable::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::Briefcase;

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

    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'creator',
                'editor',
                'baseVersion',
            ])
            ->whereIn('status', [
                WorkspaceStatusEnum::Open->value,
                WorkspaceStatusEnum::InReview->value,
                WorkspaceStatusEnum::Approved->value,
                WorkspaceStatusEnum::Scheduled->value,
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'description'];
    }

    public static function canGloballySearch(): bool
    {
        return SchemaFacade::hasTable('publishing-studio') && parent::canGloballySearch();
    }

    /**
     * @return class-string<Workspace>
     */
    #[Override]
    public static function getModel(): string
    {
        return Workspace::class;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_workflow');
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-admin.resources.workspace.icon', static::$navigationIcon);
    }

    public static function getActiveNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return config('capell-admin.resources.workspace.active_icon', static::$activeNavigationIcon);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePublishingStudio::route('/'),
            'compare' => CompareVersionPage::route('/{record}/compare'),
        ];
    }
}
