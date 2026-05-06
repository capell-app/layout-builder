<?php

declare(strict_types=1);

use Capell\Deployments\Actions\ConnectDeploymentAction;
use Capell\Deployments\Actions\OAuth\CreateOAuthStateAction;
use Capell\Deployments\Enums\GitProviderType;
use Capell\Deployments\Http\Controllers\OAuth\BitbucketCallbackController;
use Capell\Deployments\Http\Controllers\OAuth\GitHubCallbackController;
use Capell\Deployments\Http\Controllers\OAuth\GitLabCallbackController;
use Capell\Deployments\Models\DeploymentConnection;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class);

beforeEach(function (): void {
    Permission::findOrCreate('View:DeploymentConnectionPage', 'web');

    $this->actingAs($this->createUserWithPermission('View:DeploymentConnectionPage'));
});

it('ConnectDeploymentAction class exists', function (): void {
    expect(class_exists(ConnectDeploymentAction::class))->toBeTrue();
});

it('GitHub callback controller class exists', function (): void {
    expect(class_exists(GitHubCallbackController::class))->toBeTrue();
});

it('GitLab callback controller class exists', function (): void {
    expect(class_exists(GitLabCallbackController::class))->toBeTrue();
});

it('Bitbucket callback controller class exists', function (): void {
    expect(class_exists(BitbucketCallbackController::class))->toBeTrue();
});

it('rejects github oauth callbacks without a valid session state', function (): void {
    CreateOAuthStateAction::run(GitProviderType::GitHub);

    $this->get(route('capell-deployments.oauth.github', [
        'code' => 'github-code',
        'state' => 'forged-state',
    ]))
        ->assertSessionHasErrors();
});

it('connects github only after oauth state validation passes', function (): void {
    $state = CreateOAuthStateAction::run(GitProviderType::GitHub);

    Http::fake([
        'github.com/login/oauth/access_token' => Http::response([
            'access_token' => 'github-access-token',
        ]),
        'api.github.com/user' => Http::response([
            'login' => 'capell-owner',
        ]),
    ]);

    $this->get(route('capell-deployments.oauth.github', [
        'code' => 'github-code',
        'state' => $state,
    ]))
        ->assertRedirect(route('filament.admin.pages.deployment-connection'));

    expect(DeploymentConnection::query()->where([
        'provider' => GitProviderType::GitHub->value,
        'repo_owner' => 'capell-owner',
        'repo_name' => 'app',
    ])->exists())->toBeTrue();
});
