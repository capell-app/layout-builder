<?php

declare(strict_types=1);

namespace Capell\PublicActions\Http\Controllers\Zapier;

use Capell\PublicActions\Actions\BuildPublicActionIntegrationQueryAction;
use Capell\PublicActions\Enums\PublicActionIntegrationTokenAbility;
use Capell\PublicActions\Models\PublicAction;
use Capell\PublicActions\Models\PublicActionIntegrationToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListZapierPublicActionsController
{
    public function __construct(
        private readonly BuildPublicActionIntegrationQueryAction $buildQuery,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $token = $this->token($request);

        abort_unless($token->hasAbility(PublicActionIntegrationTokenAbility::ListActions), 403);

        $actions = $this->buildQuery
            ->handle($token)
            ->orderBy('name')
            ->get(['id', 'key', 'name'])
            ->map(fn (PublicAction $action): array => [
                'id' => (string) $action->getKey(),
                'key' => $action->key,
                'name' => $action->name,
            ])
            ->values();

        return response()->json(['actions' => $actions]);
    }

    private function token(Request $request): PublicActionIntegrationToken
    {
        $token = $request->attributes->get('public_action_integration_token');
        abort_unless($token instanceof PublicActionIntegrationToken, 401);

        return $token;
    }
}
