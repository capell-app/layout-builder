<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Bridges;

use Capell\Admin\Contracts\Bridges\AdminBridge;
use Capell\Admin\Contracts\Extenders\UserSchemaExtender;
use Capell\Admin\Data\Bridges\AdminBridgeContextData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Filament\Widgets\Dashboard\MyWorkQueueWidget;
use Capell\Admin\Filament\Widgets\Dashboard\RecentlyPublishedWidget;
use Capell\Admin\Support\Bridges\AdminBridgeRegistrar;
use Capell\Admin\Support\Extensions\ExtensionPageRegistry;
use Capell\PublishingStudio\Extenders\PublishingStudioUserSchemaExtender;
use Capell\PublishingStudio\Filament\Pages\ActivityTrailPage;
use Capell\PublishingStudio\Filament\Pages\ImportPagesPage;
use Capell\PublishingStudio\Filament\Pages\PublishingWorkflowPage;
use Capell\PublishingStudio\Filament\Pages\ScheduledPublishingPage;
use Capell\PublishingStudio\Filament\Pages\StaleDraftsPage;
use Capell\PublishingStudio\Filament\Resources\PreviewLinks\PreviewLinkResource;
use Capell\PublishingStudio\Filament\Resources\PublishingStudio\WorkspaceResource;
use Capell\PublishingStudio\Filament\Widgets\WorkspaceActivityWidgetAbstract;

final class PublishingStudioAdminBridge implements AdminBridge
{
    public function isEnabled(AdminBridgeContextData $context): bool
    {
        return true;
    }

    public function register(AdminBridgeRegistrar $registrar, AdminBridgeContextData $context): void
    {
        $registrar->schemaExtender(PublishingStudioUserSchemaExtender::class, UserSchemaExtender::TAG);
        $registrar->dashboardWidget(MyWorkQueueWidget::class, DashboardEnum::Main);
        $registrar->dashboardWidget(RecentlyPublishedWidget::class, DashboardEnum::Main);
        $registrar->dashboardWidget(WorkspaceActivityWidgetAbstract::class, DashboardEnum::Main);
        $registrar->resource(WorkspaceResource::class, group: 'Workspace');
        $registrar->resource(PreviewLinkResource::class, group: 'PreviewLink');

        $this->extensionPage($registrar, $context->packageName, PublishingWorkflowPage::class);
        $this->extensionPage($registrar, $context->packageName, ActivityTrailPage::class);
        $this->extensionPage($registrar, $context->packageName, ImportPagesPage::class);
        $this->extensionPage($registrar, $context->packageName, ScheduledPublishingPage::class);
        $this->extensionPage($registrar, $context->packageName, StaleDraftsPage::class);
    }

    private function extensionPage(AdminBridgeRegistrar $registrar, string $packageName, string $page): void
    {
        if (method_exists($registrar, 'extensionPage')) {
            $registrar->extensionPage($packageName, $page);

            return;
        }

        resolve(ExtensionPageRegistry::class)->register($packageName, $page);
    }
}
