<?php

declare(strict_types=1);

namespace Capell\PublicActions\Http\Controllers\Zapier;

use Capell\PublicActions\Actions\ResolvePublicActionForIntegrationTokenAction;
use Capell\PublicActions\Actions\SubmitPublicActionAction;
use Capell\PublicActions\Enums\PublicActionIntegrationTokenAbility;
use Capell\PublicActions\Models\PublicAction;
use Capell\PublicActions\Models\PublicActionIntegrationToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SubmitZapierPublicActionController
{
    public function __construct(
        private readonly ResolvePublicActionForIntegrationTokenAction $resolveAction,
        private readonly SubmitPublicActionAction $submitPublicAction,
    ) {}

    public function __invoke(Request $request, string $action): JsonResponse
    {
        $token = $this->token($request);

        abort_unless($token->hasAbility(PublicActionIntegrationTokenAbility::SubmitActions), 403);

        $publicAction = $this->resolveAction->handle($token, $action);

        abort_unless($publicAction instanceof PublicAction, 404);

        $result = $this->submitPublicAction->handle($publicAction, [
            ...$request->all(),
            'source_type' => 'zapier',
        ], $request);

        return response()->json([
            'success' => $result->success,
            'message' => $result->message,
            'redirect_url' => $result->redirectUrl,
            'created_model_type' => $result->createdModelType,
            'created_model_id' => $result->createdModelId,
        ], $result->success ? 200 : 422);
    }

    private function token(Request $request): PublicActionIntegrationToken
    {
        $token = $request->attributes->get('public_action_integration_token');
        abort_unless($token instanceof PublicActionIntegrationToken, 401);

        return $token;
    }
}
