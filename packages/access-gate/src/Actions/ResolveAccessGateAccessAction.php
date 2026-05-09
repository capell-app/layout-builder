<?php

declare(strict_types=1);

namespace Capell\AccessGate\Actions;

use Capell\AccessGate\Data\AccessGateAccessResultData;
use Capell\AccessGate\Enums\AccessAreaStatus;
use Capell\AccessGate\Enums\BrowserTokenStatus;
use Capell\AccessGate\Enums\GrantStatus;
use Capell\AccessGate\Enums\GrantSubjectType;
use Capell\AccessGate\Enums\IdentityMode;
use Capell\AccessGate\Models\Area;
use Capell\AccessGate\Models\BrowserToken;
use Capell\AccessGate\Models\Grant;
use Capell\Core\Actions\LoadSiteDomainFromUrlAction;
use Capell\Core\Models\SiteDomain;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Throwable;

final class ResolveAccessGateAccessAction
{
    use AsAction;

    /**
     * @param  list<string>  $areaKeys
     */
    public function handle(Request $request, array $areaKeys): AccessGateAccessResultData
    {
        if (! Schema::hasTable((new Area)->getTable())) {
            return new AccessGateAccessResultData(true);
        }

        $siteScopeEnabled = $this->siteScopeEnabled();
        $siteId = $siteScopeEnabled ? $this->siteId($request) : null;

        $baseAreasQuery = Area::query()
            ->whereIn('key', $areaKeys);

        $areaExists = (clone $baseAreasQuery)->exists();

        $areas = $baseAreasQuery
            ->when($siteScopeEnabled, function (Builder $query) use ($siteId): void {
                $query->where(function (Builder $query) use ($siteId): void {
                    $query->whereNull('site_id');

                    if ($siteId !== null) {
                        $query->orWhere('site_id', $siteId);
                    }
                });
            })
            ->get();

        if ($areas->isEmpty()) {
            return new AccessGateAccessResultData($areaExists && $siteScopeEnabled && $siteId !== null);
        }

        if ($areas->where('status', AccessAreaStatus::Active)->isEmpty()) {
            return new AccessGateAccessResultData(true);
        }

        $deniedResult = null;

        foreach ($areas as $area) {
            if ($area->status !== AccessAreaStatus::Active) {
                continue;
            }

            $result = $this->resolveArea($request, $area);

            if ($result->allowed) {
                return $result;
            }

            $deniedResult ??= $result;
        }

        return $deniedResult ?? new AccessGateAccessResultData(false);
    }

    private function siteScopeEnabled(): bool
    {
        return Schema::hasColumn((new Area)->getTable(), 'site_id');
    }

    private function siteId(Request $request): ?int
    {
        if (! Schema::hasTable('sites') || ! Schema::hasTable('site_domains')) {
            return null;
        }

        $resolved = LoadSiteDomainFromUrlAction::run($request->fullUrl());
        $siteDomain = is_array($resolved) ? ($resolved[0] ?? null) : null;

        return $siteDomain instanceof SiteDomain ? $siteDomain->site_id : null;
    }

    private function resolveArea(Request $request, Area $area): AccessGateAccessResultData
    {
        if ($this->matchesPublicAllowlist($request, $area)) {
            return new AccessGateAccessResultData(true, $area);
        }

        if ($area->identity_mode === IdentityMode::Authenticated || $area->identity_mode === IdentityMode::Hybrid) {
            $grant = $this->activeAuthenticatedGrant($area, $request);

            if ($grant instanceof Grant) {
                return new AccessGateAccessResultData(true, $area, $grant);
            }
        }

        if ($area->identity_mode === IdentityMode::GuestLink || $area->identity_mode === IdentityMode::Hybrid) {
            $browserToken = $this->activeBrowserToken($area, $this->plainBrowserToken($request));

            if ($browserToken instanceof BrowserToken) {
                $browserToken->forceFill(['last_used_at' => now()])->save();

                return new AccessGateAccessResultData(true, $area, $browserToken->grant, $browserToken);
            }
        }

        return new AccessGateAccessResultData(false, $area);
    }

    private function activeAuthenticatedGrant(Area $area, Request $request): ?Grant
    {
        return $this->activeUserGrant($area, $request->user()?->getAuthIdentifier())
            ?? $this->activeEmailGrant($area, $this->authenticatedEmail($request));
    }

    private function activeUserGrant(Area $area, mixed $userId): ?Grant
    {
        if (! is_int($userId) && ! is_string($userId)) {
            return null;
        }

        return Grant::query()
            ->where('access_area_id', $area->getKey())
            ->where('subject_type', GrantSubjectType::User->value)
            ->where('subject_id', (string) $userId)
            ->where($this->activeGrantScope())
            ->first();
    }

    private function activeEmailGrant(Area $area, ?string $email): ?Grant
    {
        if ($email === null || $email === '') {
            return null;
        }

        return Grant::query()
            ->where('access_area_id', $area->getKey())
            ->where('subject_type', GrantSubjectType::Email->value)
            ->where('subject_id', Str::lower($email))
            ->where($this->activeGrantScope())
            ->first();
    }

    private function activeBrowserToken(Area $area, ?string $plainTextToken): ?BrowserToken
    {
        if ($plainTextToken === null || $plainTextToken === '') {
            return null;
        }

        return BrowserToken::query()
            ->where('access_area_id', $area->getKey())
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->where('status', BrowserTokenStatus::Active->value)
            ->whereNull('revoked_at')
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->whereHas('grant', function (Builder $query): void {
                $query->where($this->activeGrantScope());
            })
            ->first();
    }

    /**
     * @return callable(Builder): void
     */
    private function activeGrantScope(): callable
    {
        return function (Builder $query): void {
            $query
                ->where('status', GrantStatus::Active->value)
                ->whereNull('revoked_at')
                ->where(function (Builder $startsAtQuery): void {
                    $startsAtQuery->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function (Builder $expiresAtQuery): void {
                    $expiresAtQuery->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
        };
    }

    private function authenticatedEmail(Request $request): ?string
    {
        $email = data_get($request->user(), 'email');

        return is_string($email) && $email !== '' ? $email : null;
    }

    private function matchesPublicAllowlist(Request $request, Area $area): bool
    {
        $allowlist = $area->public_allowlist ?? [];

        if ($allowlist === []) {
            return false;
        }

        foreach ($allowlist as $entry) {
            if ($this->allowlistEntryMatches($request, $entry)) {
                return true;
            }
        }

        return false;
    }

    private function allowlistEntryMatches(Request $request, mixed $entry): bool
    {
        if (is_string($entry)) {
            if ($entry === $request->getHost()) {
                return true;
            }

            if ($entry === $request->fullUrl()) {
                return true;
            }

            return $request->is(ltrim($entry, '/'));
        }

        if (! is_array($entry)) {
            return false;
        }

        $host = $entry['host'] ?? null;
        $path = $entry['path'] ?? null;

        if (is_string($host) && $host !== $request->getHost()) {
            return false;
        }

        if (is_string($path) && ! $request->is(ltrim($path, '/'))) {
            return false;
        }

        return is_string($host) || is_string($path);
    }

    private function plainBrowserToken(Request $request): ?string
    {
        $cookieName = config('access-gate.cookies.browser_token.name', 'capell_access_gate_browser_token');

        if (! is_string($cookieName)) {
            return null;
        }

        $value = $request->cookies->get($cookieName);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $this->decryptBrowserTokenCookie($cookieName, $value) ?? $value;
    }

    private function decryptBrowserTokenCookie(string $cookieName, string $value): ?string
    {
        try {
            $encrypter = resolve(Encrypter::class);
            $decrypted = $encrypter->decrypt($value, false);
        } catch (DecryptException|Throwable) {
            return null;
        }

        if (! is_string($decrypted)) {
            return null;
        }

        return CookieValuePrefix::validate($cookieName, $decrypted, $encrypter->getAllKeys()) ?? $decrypted;
    }
}
