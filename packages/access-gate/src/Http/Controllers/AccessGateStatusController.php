<?php

declare(strict_types=1);

namespace Capell\AccessGate\Http\Controllers;

use Capell\AccessGate\Actions\ResolveAccessGateAccessAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AccessGateStatusController
{
    public function __construct(
        private readonly ResolveAccessGateAccessAction $resolveAccess,
    ) {}

    public function __invoke(Request $request, string $area): JsonResponse
    {
        $result = $this->resolveAccess->handle($request, [$area]);

        $response = response()->json([
            'allowed' => $result->allowed,
        ]);

        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
