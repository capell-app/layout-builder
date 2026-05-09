<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Bridges;

use Capell\Admin\Contracts\Bridges\AdminBridge;
use Capell\Admin\Contracts\Extenders\UserFormExtender;
use Capell\Admin\Contracts\Extenders\UserTableExtender;
use Capell\Admin\Data\Bridges\AdminBridgeContextData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
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
        CapellAdmin::registerExtensionPage($context->packageName, PasswordPolicySettingsPage::class);

        $registrar->page(ForcedPasswordChangePage::class);
        $registrar->panelExtender(PasswordPolicyPanelExtender::class);

        app()->tag([PasswordPolicyUserFormExtender::class], UserFormExtender::TAG);
        app()->tag([PasswordPolicyUserTableExtender::class], UserTableExtender::TAG);
    }
}
