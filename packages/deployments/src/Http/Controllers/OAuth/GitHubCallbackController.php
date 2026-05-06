<?php

declare(strict_types=1);

namespace Capell\Deployments\Http\Controllers\OAuth;

use Capell\Deployments\Actions\ConnectDeploymentAction;
use Capell\Deployments\Actions\OAuth\ValidateOAuthStateAction;
use Capell\Deployments\Enums\GitProviderType;
use Capell\Deployments\Filament\Pages\DeploymentConnectionPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GitHubCallbackController
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(DeploymentConnectionPage::canAccess(), 403);

        if (! ValidateOAuthStateAction::run(GitProviderType::GitHub, $request->query('state'))) {
            return back()->withErrors([__('capell-deployments::plugins.deployment_connection.oauth_invalid_state')]);
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return back()->withErrors([__('capell-deployments::plugins.deployment_connection.oauth_missing_code')]);
        }

        $tokenResponse = Http::withHeaders(['Accept' => 'application/json'])
            ->post('https://github.com/login/oauth/access_token', [
                'client_id' => config('capell-deployments.oauth.github.client_id'),
                'client_secret' => config('capell-deployments.oauth.github.client_secret'),
                'code' => $code,
            ])
            ->json();

        $accessToken = $tokenResponse['access_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            Log::warning('capell-deployments: GitHub OAuth token exchange failed', $tokenResponse);

            return back()->withErrors([__('capell-deployments::plugins.deployment_connection.oauth_failed', ['provider' => 'GitHub'])]);
        }

        $userResponse = Http::withToken($accessToken)
            ->withHeader('Accept', 'application/vnd.github+json')
            ->get('https://api.github.com/user')
            ->json();

        $login = $userResponse['login'] ?? null;
        if (! is_string($login) || $login === '') {
            return back()->withErrors([__('capell-deployments::plugins.deployment_connection.oauth_user_failed', ['provider' => 'GitHub'])]);
        }

        // Store without repo coordinates — user selects repo on the connection page
        ConnectDeploymentAction::run(
            provider: GitProviderType::GitHub,
            repoOwner: $login,
            repoName: 'app',
            accessToken: $accessToken,
        );

        return to_route('filament.admin.pages.deployment-connection')
            ->with('status', __('capell-deployments::plugins.deployment_connection.oauth_connected', ['provider' => 'GitHub']));
    }
}
