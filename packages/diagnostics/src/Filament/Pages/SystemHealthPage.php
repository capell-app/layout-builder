<?php

declare(strict_types=1);

namespace Capell\Diagnostics\Filament\Pages;

use BackedEnum;
use BadMethodCallException;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;
use Override;

final class SystemHealthPage extends Dashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $slug = 'system-health';

    protected static ?string $title = 'System health';

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-admin::navigation.system_health');
    }

    #[Override]
    public static function getNavigationGroup(): string
    {
        return (string) (__('capell-admin::navigation.group_monitoring'));
    }

    public static function getRoutePath(Panel $panel): string
    {
        return '/' . self::getSlug($panel);
    }

    public static function canAccess(): bool
    {
        if (config('capell.dashboard.system_health_enabled', true) === false) {
            return false;
        }

        $user = auth()->user();
        if ($user === null) {
            return false;
        }

        try {
            $superAdminRole = config('capell.roles.super_admin', 'super_admin');

            return $user->hasRole($superAdminRole);
        } catch (BadMethodCallException) {
            return false;
        }
    }

    /**
     * @return array<class-string<Widget>|WidgetConfiguration>
     */
    public function getWidgets(): array
    {
        return CapellAdmin::getDashboardWidgets(DashboardEnum::SystemHealth);
    }

    public function getColumns(): array
    {
        return ['default' => 1, 'md' => 3];
    }
}
