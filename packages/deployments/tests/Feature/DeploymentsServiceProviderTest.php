<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Deployments\Contracts\PublishesComposerChanges;
use Capell\Deployments\Filament\Pages\DeploymentConnectionPage;
use Capell\Deployments\Providers\DeploymentsServiceProvider;

it('registers the deployments package metadata', function (): void {
    expect(CapellCore::hasPackage(DeploymentsServiceProvider::$packageName))->toBeTrue()
        ->and(CapellCore::getPackage(DeploymentsServiceProvider::$packageName)->serviceProviderClass)
        ->toBe(DeploymentsServiceProvider::class);
});

it('registers the deployment connections admin page', function (): void {
    expect(CapellAdmin::getAdminSurfaceRegistry()->pages())->toContain(DeploymentConnectionPage::class)
        ->and(DeploymentConnectionPage::getNavigationLabel())->toBe('Deployment Repository')
        ->and(app()->bound(PublishesComposerChanges::class))->toBeTrue();
});
