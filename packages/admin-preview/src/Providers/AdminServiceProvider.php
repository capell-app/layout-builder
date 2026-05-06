<?php

declare(strict_types=1);

namespace Capell\AdminPreview\Providers;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\AdminPreview\Filament\Extenders\AdminPreviewAdminPanelExtender;
use Capell\AdminPreview\PublishingStudio\WorkspacePeekPreviewActionContributor;
use Capell\Core\Facades\CapellCore;
use Capell\PublishingStudio\Contracts\WorkspaceTableActionContributor;
use Illuminate\Support\ServiceProvider;

final class AdminServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (! $this->isPackageInstalled()) {
            return;
        }

        $this->app->tag([AdminPreviewAdminPanelExtender::class], AdminPanelExtender::TAG);
        $this->app->tag([WorkspacePeekPreviewActionContributor::class], WorkspaceTableActionContributor::TAG);
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(AdminPreviewServiceProvider::$packageName);
    }
}
