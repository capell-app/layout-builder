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

final class BitbucketCallbackController
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(DeploymentConnectionPage::canAccess(), 403);

        if (! ValidateOAuthStateAction::run(GitProviderType::Bitbucket, $request->query('state'))) {
            return back()->withErrors([__('capell-deployments::plugins.deployment_connection.oauth_invalid_state')]);
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return back()->withErrors([__('capell-deployments::plugins.deployment_connection.oauth_missing_code')]);
        }

        $clientId = config('capell-deployments.oauth.bitbucket.client_id');
        $clientSecret = config('capell-deployments.oauth.bitbucket.client_secret');

        $tokenResponse = Http::withBasicAuth((string) $clientId, (string) $clientSecret)
            ->asForm()
            ->post('https://bitbucket.org/site/oauth2/access_token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => route('capell-deployments.oauth.bitbucket'),
            ])
            ->json();

        $accessToken = $tokenResponse['access_token'] ?? null;
        $refreshToken = $tokenResponse['refresh_token'] ?? null;
        if (! is_string($accessToken) || $accessToken === '') {
            Log::warning('capell-deployments: Bitbucket OAuth token exchange failed', $tokenResponse);

            return back()->withErrors([__('capell-deployments::plugins.deployment_connection.oauth_failed', ['provider' => 'Bitbucket'])]);
        }

        $userResponse = Http::withToken($accessToken)
            ->get('https://api.bitbucket.org/2.0/user')
            ->json();

        $username = $userResponse['username'] ?? null;
        if (! is_string($username) || $username === '') {
            return back()->withErrors([__('capell-deployments::plugins.deployment_connection.oauth_user_failed', ['provider' => 'Bitbucket'])]);
        }

        ConnectDeploymentAction::run(
            provider: GitProviderType::Bitbucket,
            repoOwner: $username,
            repoName: 'app',
            accessToken: $accessToken,
            refreshToken: is_string($refreshToken) ? $refreshToken : null,
        );

        return to_route('filament.admin.pages.deployment-connection')
            ->with('status', __('capell-deployments::plugins.deployment_connection.oauth_connected', ['provider' => 'Bitbucket']));
    }
}
