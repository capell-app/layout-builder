<?php

declare(strict_types=1);

namespace Capell\Deployments\Actions\OAuth;

use Capell\Deployments\Enums\GitProviderType;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateOAuthStateAction
{
    use AsAction;

    public function handle(GitProviderType $provider): string
    {
        $state = Str::random(40);

        session()->put($this->sessionKey($provider), $state);

        return $state;
    }

    private function sessionKey(GitProviderType $provider): string
    {
        return sprintf('capell-deployments.oauth_state.%s', $provider->value);
    }
}
