<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Bridges;

use Capell\Admin\Contracts\Bridges\AdminBridge;
use Capell\Admin\Contracts\DashboardSettingsContributor;
use Capell\Admin\Contracts\Extenders\UserSchemaExtender;
use Capell\Admin\Data\Bridges\AdminBridgeContextData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
use Capell\LoginAudit\Extenders\LoginAuditUserSchemaExtender;
use Capell\LoginAudit\Filament\Extenders\LoginAuditAdminPanelExtender;
use Capell\LoginAudit\Filament\Resources\LoginAudits\LoginAuditResource;
use Capell\LoginAudit\Filament\Settings\Contributors\LoginAuditDashboardSettingsContributor;
use Capell\LoginAudit\Filament\Widgets\LoginAuditsWidget;

final class LoginAuditAdminBridge implements AdminBridge
{
    public function isEnabled(AdminBridgeContextData $context): bool
    {
        return true;
    }

    public function register(AdminBridgeRegistrar $registrar, AdminBridgeContextData $context): void
    {
        $registrar->schemaExtender(LoginAuditUserSchemaExtender::class, UserSchemaExtender::TAG);
        $registrar->panelExtender(LoginAuditAdminPanelExtender::class);
        $registrar->resource(LoginAuditResource::class, group: 'LoginAudit');
        $registrar->dashboardWidget(LoginAuditsWidget::class, DashboardEnum::SystemHealth);

        app()->tag([LoginAuditDashboardSettingsContributor::class], DashboardSettingsContributor::TAG);
    }
}
