<?php

declare(strict_types=1);

namespace Capell\Workspaces\Filament\Resources\Workspaces;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredForm;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Filament\Resources\Workspaces\Pages\CompareVersionPage;
use Capell\Workspaces\Filament\Resources\Workspaces\Pages\ManageWorkspaces;
use Capell\Workspaces\Filament\Resources\Workspaces\Schemas\WorkspaceForm;
use Capell\Workspaces\Filament\Resources\Workspaces\Tables\WorkspacesTable;
use Capell\Workspaces\Models\Workspace;
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

    protected static string $tableConfigurator = WorkspacesTable::class;

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
        return SchemaFacade::hasTable('workspaces') && parent::canGloballySearch();
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
        return __('capell-admin::navigation.group_administration');
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
            'index' => ManageWorkspaces::route('/'),
            'compare' => CompareVersionPage::route('/{record}/compare'),
        ];
    }
}
