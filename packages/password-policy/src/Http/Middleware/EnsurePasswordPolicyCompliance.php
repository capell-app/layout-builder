<?php

declare(strict_types=1);

namespace Capell\PasswordPolicy\Http\Middleware;

use Capell\PasswordPolicy\Actions\EvaluatePasswordPolicyAction;
use Capell\PasswordPolicy\Filament\Pages\ForcedPasswordChangePage;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordPolicyCompliance
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Model || $this->isAllowedRoute($request)) {
            return $next($request);
        }

        if (! EvaluatePasswordPolicyAction::run($user)->shouldRedirect()) {
            return $next($request);
        }

        return redirect(ForcedPasswordChangePage::getUrl());
    }

    private function isAllowedRoute(Request $request): bool
    {
        return $request->is('admin/password-policy/change-password')
            || $request->routeIs('filament.admin.auth.logout');
    }
}
