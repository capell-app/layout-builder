<?php

declare(strict_types=1);

namespace Capell\Deployments\Actions\OAuth;

use Capell\Deployments\Enums\GitProviderType;
use Lorisleiva\Actions\Concerns\AsAction;

final class ValidateOAuthStateAction
{
    use AsAction;

    public function handle(GitProviderType $provider, mixed $state): bool
    {
        if (! is_string($state) || $state === '') {
            return false;
        }

        $expectedState = session()->pull($this->sessionKey($provider));

        if (! is_string($expectedState) || $expectedState === '') {
            return false;
        }

        return hash_equals($expectedState, $state);
    }

    private function sessionKey(GitProviderType $provider): string
    {
        return sprintf('capell-deployments.oauth_state.%s', $provider->value);
    }
}
