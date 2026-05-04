<?php

declare(strict_types=1);

namespace Capell\Migrator\Filament\Resources\ImportSessions;

use BackedEnum;
use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\Admin\Filament\Concerns\HasNavigationBadge;
use Capell\Migrator\Contracts\ImportSessionSubNavigationExtender;
use Capell\Migrator\Filament\Pages\ImportSitesPage;
use Capell\Migrator\Filament\Resources\ImportSessions\Pages\ListImportSessions;
use Capell\Migrator\Filament\Resources\ImportSessions\Pages\ViewImportSession;
use Capell\Migrator\Filament\Resources\ImportSessions\Tables\ImportSessionsTable;
use Capell\Migrator\Models\ImportSession;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Override;

class ImportSessionResource extends Resource
{
    use HasConfiguredTable;
    use HasNavigationBadge;

    protected static ?string $model = ImportSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::ArrowPath;

    protected static ?string $recordTitleAttribute = 'uuid';

    protected static string $tableConfigurator = ImportSessionsTable::class;

    public static function shouldRegisterWithPanel(): bool
    {
        return class_exists(ImportSession::class);
    }

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getNavigationLabel(): string
    {
        return (string) __('capell-admin::exchanger.import_sessions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('capell-admin::navigation.group_administration');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('capell-admin::exchanger.import_sessions');
    }

    public static function getModelLabel(): string
    {
        return (string) __('capell-admin::exchanger.import_session');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterWithPanel() && parent::canAccess();
    }

    public static function canGloballySearch(): bool
    {
        return SchemaFacade::hasTable('import_sessions') && parent::canGloballySearch();
    }

    public static function getSubNavigation(): array
    {
        $items = [];

        foreach (app()->tagged(ImportSessionSubNavigationExtender::TAG) as $extender) {
            /** @var ImportSessionSubNavigationExtender $extender */
            $items = array_merge($items, $extender->getItems());
        }

        $items[] = NavigationItem::make()
            ->label(__('capell-admin::exchanger.import_sites'))
            ->icon(Heroicon::OutlinedGlobeAlt)
            ->url(ImportSitesPage::getUrl())
            ->isActiveWhen(fn (): bool => request()->is('*/recovery-center/import-sites*'));

        return $items;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportSessions::route('/'),
            'view' => ViewImportSession::route('/{record}'),
        ];
    }
}
