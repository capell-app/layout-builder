<?php

declare(strict_types=1);

namespace Capell\Mcp\Http\Middleware;

use Capell\Mcp\Data\AuthenticatedMcpClientData;
use Capell\Mcp\Models\CapellMcpToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateCapellMcpToken
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = (string) $request->bearerToken();

        if ($plainTextToken === '') {
            return $this->unauthorized('Missing MCP bearer token.');
        }

        $token = CapellMcpToken::query()
            ->where('token_hash', CapellMcpToken::hashPlainTextToken($plainTextToken))
            ->first();

        if (! $token instanceof CapellMcpToken || $token->isExpired()) {
            return $this->unauthorized('Invalid MCP bearer token.');
        }

        $user = $token->user;

        if ($user === null) {
            return $this->unauthorized('MCP token is not linked to a user.');
        }

        $guard = config('capell-mcp.site_auth_guard', 'web');
        $authGuard = Auth::guard((string) $guard);
        $authGuard->setUser($user);
        Auth::shouldUse((string) $guard);

        $token->forceFill(['last_used_at' => now()])->save();

        app()->instance(CapellMcpToken::class, $token);
        app()->instance(AuthenticatedMcpClientData::class, new AuthenticatedMcpClientData(
            tokenId: (int) $token->getKey(),
            name: $token->name,
            scopes: $token->scopes,
        ));

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response($message, 401)
            ->header('WWW-Authenticate', 'Bearer realm="capell-mcp", error="invalid_token"');
    }
}
