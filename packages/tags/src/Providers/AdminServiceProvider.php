<?php

declare(strict_types=1);

namespace Capell\Tags\Providers;

use Capell\Admin\Data\AdminSurfaceContributionData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Tags\Enums\ResourceEnum;
use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
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

        CapellAdmin::contributeToAdminSurface(AdminSurfaceContributionData::resource(
            class: ResourceEnum::Tag->value,
            group: ResourceEnum::Tag->name,
        ));
    }

    private function isPackageInstalled(): bool
    {
        return CapellCore::isPackageInstalled(TagsServiceProvider::$packageName);
    }
}
