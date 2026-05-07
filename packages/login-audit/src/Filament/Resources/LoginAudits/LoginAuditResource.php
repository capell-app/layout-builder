<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Filament\Resources\LoginAudits;

use Capell\Admin\Filament\Concerns\HasConfiguredTable;
use Capell\LoginAudit\Filament\Resources\LoginAudits\Tables\LoginAuditsTable;
use Filament\Tables\Table;
use Override;
use Tapp\FilamentAuthenticationLog\Resources\AuthenticationLogResource;

class LoginAuditResource extends AuthenticationLogResource
{
    use HasConfiguredTable;

    protected static string $tableConfigurator = LoginAuditsTable::class;

    #[Override]
    public static function table(Table $table): Table
    {
        return static::getTableConfigurator()::configure($table);
    }

    #[Override]
    public static function getNavigationLabel(): string
    {
        return (string) __('capell-login-audit::navigation.login_audits');
    }

    #[Override]
    public static function getNavigationGroup(): ?string
    {
        return (string) __('capell-admin::navigation.group_users');
    }
}
