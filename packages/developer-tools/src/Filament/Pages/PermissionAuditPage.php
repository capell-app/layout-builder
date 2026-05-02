<?php

declare(strict_types=1);

namespace Capell\DeveloperTools\Filament\Pages;

use BackedEnum;
use Capell\DeveloperTools\Filament\Pages\Tables\PermissionAuditTable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class PermissionAuditPage extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $slug = 'reports/permission-audit';

    protected static ?string $title = 'Permission Audit';

    protected static ?int $navigationSort = 3;

    protected string $view = 'capell-admin::components.pages.table';

    public static function getNavigationLabel(): string
    {
        return (string) __('capell-admin::navigation.permission_audit');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:' . class_basename(static::class)) ?? false;
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_monitoring'));
    }

    public function table(Table $table): Table
    {
        return PermissionAuditTable::configure($table);
    }
}
