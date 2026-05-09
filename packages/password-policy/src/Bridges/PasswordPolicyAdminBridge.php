<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Bridges;

use Capell\Admin\Contracts\Bridges\AdminBridge;
use Capell\Admin\Contracts\Bridges\UserResourceBridge;
use Capell\Admin\Data\Bridges\AdminBridgeContextData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\Bridges\AbstractUserResourceBridge;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
use Capell\PasswordPolicy\Filament\Extenders\PasswordPolicyPanelExtender;
use Capell\PasswordPolicy\Filament\Extenders\PasswordPolicyUserFormExtender;
use Capell\PasswordPolicy\Filament\Extenders\PasswordPolicyUserTableExtender;
use Capell\PasswordPolicy\Filament\Pages\ForcedPasswordChangePage;
use Capell\PasswordPolicy\Filament\Pages\PasswordPolicySettingsPage;
use Illuminate\Database\Eloquent\Model;

final class PasswordPolicyAdminBridge extends AbstractUserResourceBridge implements AdminBridge
{
    public function isEnabled(AdminBridgeContextData $context): bool
    {
        return true;
    }

    public function register(AdminBridgeRegistrar $registrar, AdminBridgeContextData $context): void
    {
        CapellAdmin::registerExtensionPage($context->packageName, PasswordPolicySettingsPage::class);

        $registrar->page(ForcedPasswordChangePage::class);
        $registrar->panelExtender(PasswordPolicyPanelExtender::class);

        app()->tag([self::class], UserResourceBridge::TAG);
    }

    public function mutateDataBeforeCreate(array $data): array
    {
        return resolve(PasswordPolicyUserFormExtender::class)->mutateDataBeforeCreate($data);
    }

    public function afterCreate(Model $record): void
    {
        resolve(PasswordPolicyUserFormExtender::class)->afterCreate($record);
    }

    public function mutateDataBeforeSave(Model $record, array $data): array
    {
        return resolve(PasswordPolicyUserFormExtender::class)->mutateDataBeforeSave($record, $data);
    }

    public function afterSave(Model $record): void
    {
        resolve(PasswordPolicyUserFormExtender::class)->afterSave($record);
    }

    public function columns(): array
    {
        return resolve(PasswordPolicyUserTableExtender::class)->columns();
    }

    public function filters(): array
    {
        return resolve(PasswordPolicyUserTableExtender::class)->filters();
    }

    public function recordActions(): array
    {
        return resolve(PasswordPolicyUserTableExtender::class)->recordActions();
    }

    public function toolbarActions(): array
    {
        return resolve(PasswordPolicyUserTableExtender::class)->toolbarActions();
    }
}
