<?php

declare(strict_types=1);

namespace Capell\PublicActions\Actions;

use Capell\PublicActions\Models\PublicAction;
use Capell\PublicActions\Models\PublicActionIntegrationToken;
use Lorisleiva\Actions\Concerns\AsAction;

final class ResolvePublicActionForIntegrationTokenAction
{
    use AsAction;

    public function __construct(
        private readonly BuildPublicActionIntegrationQueryAction $buildQuery,
    ) {}

    public function handle(PublicActionIntegrationToken $token, string $key): ?PublicAction
    {
        return $this->buildQuery
            ->handle($token)
            ->where('key', $key)
            ->first();
    }
}
