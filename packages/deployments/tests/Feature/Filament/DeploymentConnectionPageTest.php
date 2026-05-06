<?php

declare(strict_types=1);

use Capell\Deployments\Filament\Pages\DeploymentConnectionPage;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

it('moves deployment repository connections into the deployments package', function (): void {
    expect(class_exists(DeploymentConnectionPage::class))->toBeTrue();
});

it('DeploymentConnectionPage class exists and has correct slug', function (): void {
    expect(class_exists(DeploymentConnectionPage::class))->toBeTrue();

    $property = new ReflectionProperty(DeploymentConnectionPage::class, 'slug');

    expect($property->getValue())->toBe('deployment-connection');
});

it('returns a Filament compatible navigation icon', function (): void {
    expect(DeploymentConnectionPage::getNavigationIcon())->not->toBeNull();
});

it('uses clear deployment repository navigation labels', function (): void {
    expect(DeploymentConnectionPage::getNavigationLabel())->toBe('Deployment Repository')
        ->and(DeploymentConnectionPage::getNavigationGroup())->toBe('Administration')
        ->and((new DeploymentConnectionPage)->getTitle())->toBe('Deployment Repository');
});

it('builds provider oauth urls from named callback routes', function (): void {
    config()->set('capell-deployments.oauth.github.client_id', 'github-client-id');
    config()->set('capell-deployments.oauth.gitlab.client_id', 'gitlab-client-id');
    config()->set('capell-deployments.oauth.bitbucket.client_id', 'bitbucket-client-id');

    $page = new DeploymentConnectionPage;

    expect($page->getGitHubOAuthUrl())->toContain(urlencode(route('capell-deployments.oauth.github')))
        ->and($page->getGitLabOAuthUrl())->toContain(urlencode(route('capell-deployments.oauth.gitlab')))
        ->and($page->getBitbucketOAuthUrl())->toContain('client_id=bitbucket-client-id')
        ->and($page->getGitHubOAuthUrl())->toContain('state=')
        ->and($page->getGitLabOAuthUrl())->toContain('state=')
        ->and($page->getBitbucketOAuthUrl())->toContain('state=');
});

it('does not fail when the deployment connections table has not been migrated yet', function (): void {
    Schema::dropIfExists('deployment_connections');

    expect((new DeploymentConnectionPage)->getConnections())->toBe([]);
});

it('limits access to users with the deployment connection page permission', function (): void {
    Permission::findOrCreate('View:DeploymentConnectionPage', 'web');

    expect(DeploymentConnectionPage::canAccess())->toBeFalse();

    $this->actingAsUser();

    expect(DeploymentConnectionPage::canAccess())->toBeFalse();

    $this->actingAs($this->createUserWithPermission('View:DeploymentConnectionPage'));

    expect(DeploymentConnectionPage::canAccess())->toBeTrue();
});
