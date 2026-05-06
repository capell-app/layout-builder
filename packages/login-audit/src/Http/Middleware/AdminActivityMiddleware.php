<?php

declare(strict_types=1);

namespace Capell\LoginAudit\Http\Middleware;

use Capell\LoginAudit\Actions\ResolveLoginAuditIpAddressAction;
use Capell\LoginAudit\Models\LoginAudit;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;

class AdminActivityMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (Filament::auth()->check()) {
            $this->updateUserActivity($request);
        }

        return $next($request);
    }

    private function updateUserActivity(Request $request): void
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        if ($user === null) {
            return;
        }

        $ipAddress = resolve(ResolveLoginAuditIpAddressAction::class)->handle($request);

        $userAgent = (string) $request->userAgent();

        $now = now();

        $log = LoginAudit::query()
            ->where('authenticatable_type', $user->getMorphClass())
            ->where('authenticatable_id', method_exists($user, 'getKey') ? $user->getKey() : null)
            ->when(
                $ipAddress === null,
                fn (Builder $query): Builder => $query->whereNull('ip_address'),
                fn (Builder $query): Builder => $query->where('ip_address', $ipAddress),
            )
            ->where('user_agent', $userAgent)
            ->where('login_at', '<', $now)
            ->latest('id')
            ->first();

        if ($log !== null) {
            $log->last_seen_at = $now;
            $log->save();
        }
    }
}
