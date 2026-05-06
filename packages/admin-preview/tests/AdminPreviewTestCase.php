<?php

declare(strict_types=1);

namespace Capell\AdminPreview\Tests;

use Capell\AdminPreview\Providers\AdminPreviewServiceProvider;
use Capell\Core\Facades\CapellCore;
use Capell\PublishingStudio\Models\Workspace;
use Capell\Tests\AbstractTestCase;
use Illuminate\Database\Eloquent\Relations\Relation;
use Livewire\LivewireServiceProvider;
use Override;
use Pboivin\AdminPreview\AdminPreviewServiceProvider as BaseAdminPreviewServiceProvider;

abstract class AdminPreviewTestCase extends AbstractTestCase
{
    protected function getPackageServiceName(): string
    {
        return 'capell-admin-preview';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            BaseAdminPreviewServiceProvider::class,
            AdminPreviewServiceProvider::class,
            LivewireServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::registerPackage(
            'capell-app/publishing-studio',
            path: realpath(__DIR__ . '/../../publishing-studio'),
        );
        CapellCore::forcePackageInstalled('capell-app/publishing-studio');
        CapellCore::forcePackageInstalled(AdminPreviewServiceProvider::$packageName);

        Relation::morphMap([
            'workspace' => Workspace::class,
        ]);
    }
}
