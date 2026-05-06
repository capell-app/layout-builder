<?php

declare(strict_types=1);

namespace Capell\PublishingStudio\Http\Middleware;

use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Workspace;
use Capell\PublishingStudio\WorkspaceContext;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Resolves a workspace to apply as the current context for the request.
 *
 * Precedence:
 *  1. Signed URL with `__workspace=<uuid>` query parameter — the primary
 *     mechanism for shareable previews. Drops a short-lived cookie so the
 *     preview survives navigation.
 *  2. Existing `cms_workspace` cookie (previously issued by the same signed
 *     flow or by the admin panel's switcher).
 *  3. Authenticated admin session attribute `cms_workspace_id` (the
 *     Filament switcher sets this).
 *  4. None → context remains live.
 *
 * Any permissions check is the responsibility of the gate / policy layer —
 * this middleware only resolves the identifier.
 */
final class ResolveWorkspaceContext
{
    /** @var string */
    public const QUERY_PARAM = '__workspace';

    /** @var string */
    public const TOKEN_PARAM = '__pl';

    /** @var string */
    public const COOKIE_NAME = 'cms_workspace';

    /** @var int */
    public const COOKIE_TTL_MINUTES = 240;

    /** @var string */
    public const SESSION_KEY = 'cms_workspace_id';

    public function handle(Request $request, Closure $next): Response
    {
        $viaToken = false;
        $workspace = $this->resolve($request, $viaToken);

        WorkspaceContext::set($workspace);

        $response = $next($request);

        if ($workspace instanceof Workspace
            && ! $viaToken
            && $request->hasValidSignature()
            && $request->query(self::QUERY_PARAM) !== null) {
            $response->headers->setCookie(cookie(
                self::COOKIE_NAME,
                $workspace->uuid,
                self::COOKIE_TTL_MINUTES,
                null,
                null,
                $request->isSecure(),
                true,
                false,
                'lax',
            ));
        }

        return $response;
    }

    private function resolve(Request $request, bool &$viaToken): ?Workspace
    {
        if ($request->hasValidSignature()) {
            $uuid = $request->query(self::QUERY_PARAM);
            if (is_string($uuid) && $uuid !== '') {
                $token = $request->query(self::TOKEN_PARAM);
                $previewLinksAvailable = $this->tableExists('preview_links');

                if (is_string($token) && $token !== '' && $previewLinksAvailable) {
                    $link = PreviewLink::query()->where('token', $token)->first();
                    if (! $link instanceof PreviewLink || ! $link->isUsable()) {
                        return null;
                    }

                    $workspace = Workspace::query()->whereKey($link->workspace_id)->first();
                    if (! $workspace instanceof Workspace || $workspace->uuid !== $uuid) {
                        return null;
                    }

                    $link->forceFill([
                        'last_accessed_at' => CarbonImmutable::now(),
                        'access_count' => $link->access_count + 1,
                    ])->save();

                    $viaToken = true;

                    return $workspace;
                }

                if ($previewLinksAvailable) {
                    return null;
                }

                $workspace = Workspace::query()->where('uuid', $uuid)->first();
                if ($workspace instanceof Workspace) {
                    return $workspace;
                }
            }
        }

        $cookieUuid = $request->cookie(self::COOKIE_NAME);
        if (is_string($cookieUuid) && $cookieUuid !== '') {
            $workspace = Workspace::query()->where('uuid', $cookieUuid)->first();
            if ($workspace instanceof Workspace && $this->userMayResolve($request, $workspace)) {
                return $workspace;
            }
        }

        if ($request->hasSession()) {
            $sessionId = $request->session()->get(self::SESSION_KEY);
            if (is_int($sessionId) || (is_string($sessionId) && ctype_digit($sessionId))) {
                $workspace = Workspace::query()->find((int) $sessionId);
                if ($workspace instanceof Workspace && $this->userMayResolve($request, $workspace)) {
                    return $workspace;
                }
            }
        }

        return null;
    }

    /**
     * Authenticated users must have permission to view the workspace
     * before a stored cookie/session context is trusted — this prevents
     * a user whose workspace access was revoked from continuing to
     * operate under that context. Guests are allowed through so that
     * the server-issued signed-preview cookie flow continues to work.
     */
    private function userMayResolve(Request $request, Workspace $workspace): bool
    {
        $user = $request->user();

        if ($user === null) {
            return true;
        }

        try {
            return $user->can('view', $workspace);
        } catch (Throwable) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
