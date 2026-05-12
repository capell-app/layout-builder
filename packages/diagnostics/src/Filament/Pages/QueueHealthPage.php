<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Filament\Pages;

use BackedEnum;
use BadMethodCallException;
use Capell\Diagnostics\Enums\DiagnosticsPermission;
use Capell\Diagnostics\Filament\Pages\Tables\QueueHealthTable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class QueueHealthPage extends Page implements HasActions, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $slug = 'dashboard-dashboard_reports/queue-health';

    protected static ?string $title = 'Queue Health';

    protected static ?int $navigationSort = 2;

    protected string $view = 'capell-admin::components.pages.table';

    public static function getNavigationLabel(): string
    {
        return (string) __('capell-admin::navigation.queue_health');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if ($user === null) {
            return false;
        }

        try {
            $superAdminRole = config('capell.roles.super_admin', 'super_admin');

            if (is_string($superAdminRole) && $superAdminRole !== '' && $user->hasRole($superAdminRole)) {
                return true;
            }
        } catch (BadMethodCallException) {
            // Role system not available; fall back to diagnostics permissions.
        }

        if (Gate::allows(DiagnosticsPermission::AccessDiagnostics->value)) {
            return true;
        }

        if (Gate::allows(DiagnosticsPermission::ViewDiagnostics->value)) {
            return true;
        }

        if ($user->can(DiagnosticsPermission::AccessDiagnostics->value) === true) {
            return true;
        }

        return $user->can(DiagnosticsPermission::ViewDiagnostics->value) === true;
    }

    public static function getNavigationGroup(): ?string
    {
        return (string) (__('capell-admin::navigation.group_system'));
    }

    public function table(Table $table): Table
    {
        return QueueHealthTable::configure($table);
    }
}
