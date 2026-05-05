<?php

declare(strict_types=1);

namespace Capell\PasswordSecurity\Http\Middleware;

use Capell\PasswordSecurity\Actions\EvaluatePasswordSecurityAction;
use Capell\PasswordSecurity\Filament\Pages\ForcedPasswordChangePage;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordSecurityCompliance
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Model || $this->isAllowedRoute($request)) {
            return $next($request);
        }

        if (! EvaluatePasswordSecurityAction::run($user)->shouldRedirect()) {
            return $next($request);
        }

        return redirect(ForcedPasswordChangePage::getUrl());
    }

    private function isAllowedRoute(Request $request): bool
    {
        return $request->is('admin/password-security/change-password')
            || $request->routeIs('filament.admin.auth.logout');
    }
}
