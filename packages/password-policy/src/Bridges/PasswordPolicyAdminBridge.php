<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Bridges;

use Capell\Admin\Contracts\Bridges\AdminBridge;
use Capell\Admin\Contracts\Extenders\UserFormExtender;
use Capell\Admin\Contracts\Extenders\UserTableExtender;
use Capell\Admin\Data\Bridges\AdminBridgeContextData;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
use Capell\Admin\Support\Extensions\ExtensionPageRegistry;
use Capell\PasswordPolicy\Filament\Extenders\PasswordPolicyPanelExtender;
use Capell\PasswordPolicy\Filament\Extenders\PasswordPolicyUserFormExtender;
use Capell\PasswordPolicy\Filament\Extenders\PasswordPolicyUserTableExtender;
use Capell\PasswordPolicy\Filament\Pages\ForcedPasswordChangePage;
use Capell\PasswordPolicy\Filament\Pages\PasswordPolicySettingsPage;

final class PasswordPolicyAdminBridge implements AdminBridge
{
    public function isEnabled(AdminBridgeContextData $context): bool
    {
        return true;
    }

    public function register(AdminBridgeRegistrar $registrar, AdminBridgeContextData $context): void
    {
        if (method_exists($registrar, 'extensionPage')) {
            $registrar->extensionPage($context->packageName, PasswordPolicySettingsPage::class);
        } else {
            resolve(ExtensionPageRegistry::class)->register($context->packageName, PasswordPolicySettingsPage::class);
        }

        $registrar->page(ForcedPasswordChangePage::class);
        $registrar->panelExtender(PasswordPolicyPanelExtender::class);

        if (method_exists($registrar, 'userFormExtender')) {
            $registrar->userFormExtender(PasswordPolicyUserFormExtender::class);
        } else {
            app()->tag([PasswordPolicyUserFormExtender::class], UserFormExtender::TAG);
        }

        if (method_exists($registrar, 'userTableExtender')) {
            $registrar->userTableExtender(PasswordPolicyUserTableExtender::class);
        } else {
            app()->tag([PasswordPolicyUserTableExtender::class], UserTableExtender::TAG);
        }
    }
}
