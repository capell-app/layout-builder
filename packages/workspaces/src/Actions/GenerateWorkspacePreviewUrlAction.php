<?php

declare(strict_types=1);

namespace Capell\Workspaces\Actions;

use Capell\Workspaces\Http\Middleware\ResolveWorkspaceContext;
use Capell\Workspaces\Models\PreviewLink;
use Capell\Workspaces\Models\Workspace;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;

/**
 * Builds a temporary signed URL into the frontend that carries the workspace
 * UUID as the `__workspace` query parameter plus a short-lived preview-link
 * token. The `ResolveWorkspaceContext` middleware reads both on a valid
 * signature and drops a short lived cookie so subsequent navigation stays
 * inside the preview. The token is the authoritative validity key, allowing
 * admins to revoke or extend a link after issue.
 */
class GenerateWorkspacePreviewUrlAction
{
    public function handle(Workspace $workspace, string $path = '/', ?int $ttlMinutes = null): string
    {
        $ttl = $ttlMinutes ?? ResolveWorkspaceContext::COOKIE_TTL_MINUTES;
        $now = Date::now();
        $expiresAt = $now->addMinutes($ttl);

        $actor = Auth::user();
        $link = PreviewLink::query()->create([
            'workspace_id' => $workspace->id,
            'token' => PreviewLink::generateToken(),
            'issued_by_type' => $actor instanceof Authenticatable ? $actor->getMorphClass() : null,
            'issued_by_id' => $actor instanceof Authenticatable ? $actor->getAuthIdentifier() : null,
            'issued_at' => $now,
            'expires_at' => $expiresAt,
        ]);

        $parameters = [
            ResolveWorkspaceContext::QUERY_PARAM => $workspace->uuid,
            ResolveWorkspaceContext::TOKEN_PARAM => $link->token,
        ];

        $normalizedPath = ltrim($path, '/');

        if ($normalizedPath === '' || $normalizedPath === 'index.php') {
            return URL::temporarySignedRoute('capell-frontend.index', $expiresAt, $parameters);
        }

        return URL::temporarySignedRoute(
            'capell-frontend.page',
            $expiresAt,
            array_merge(['url' => $normalizedPath], $parameters),
        );
    }
}
