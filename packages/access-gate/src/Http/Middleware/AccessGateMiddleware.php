<?php

declare(strict_types=1);

namespace Capell\AccessGate\Http\Middleware;

use Capell\AccessGate\Actions\ResolveAccessGateAccessAction;
use Capell\AccessGate\Models\Area;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AccessGateMiddleware
{
    public function __construct(
        private readonly ResolveAccessGateAccessAction $resolveAccess,
    ) {}

    public function handle(Request $request, Closure $next, string ...$parameters): Response
    {
        $areaKeys = $this->areaKeys($parameters);

        abort_if($areaKeys === [], 403);

        $result = $this->resolveAccess->handle($request, $areaKeys);

        if (! $result->allowed) {
            return $this->deny($request, $areaKeys[0]);
        }

        if ($result->area instanceof Area) {
            $this->markProtectedRequest($request);
        }

        $response = $next($request);

        if ($result->area instanceof Area) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }

    private function markProtectedRequest(Request $request): void
    {
        $request->attributes->set('access_gate.protected', true);
        $request->headers->set('Cache-Control', 'no-store, no-cache, private');
        $request->headers->set('Pragma', 'no-cache');
    }

    private function deny(Request $request, string $areaKey): Response
    {
        if ($request->expectsJson()) {
            return $this->noStore(response()->json([
                'message' => __('capell-access-gate::public.request_submitted'),
            ], 403));
        }

        return $this->noStore(to_route('capell-access-gate.request', [
            'area' => $areaKey,
            'redirect' => $request->fullUrl(),
        ]));
    }

    private function noStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    /**
     * @param  list<string>  $parameters
     * @return list<string>
     */
    private function areaKeys(array $parameters): array
    {
        if ($parameters === []) {
            return [];
        }

        if ($parameters[0] === 'any') {
            array_shift($parameters);
        }

        if (str_starts_with($parameters[0] ?? '', 'any:')) {
            $parameters[0] = substr($parameters[0], 4);
        }

        return collect($parameters)
            ->flatMap(fn (string $parameter): array => explode(',', $parameter))
            ->map(fn (string $parameter): string => trim($parameter))
            ->filter(fn (string $parameter): bool => $parameter !== '')
            ->values()
            ->all();
    }
}
