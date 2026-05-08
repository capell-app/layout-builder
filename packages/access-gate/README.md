# Capell Access Gate

Reusable gated access areas for Capell and Laravel applications.

The package owns areas, registrations, approvals, grants, claim links, browser tokens, middleware, notifications, audit events, install/setup/doctor commands, and Filament administration.

## Request Methods

The package renders the plain email request flow by default. Host applications can register additional request methods without the package knowing about the provider.

Use this for app-owned flows such as GitHub OAuth:

```php
use Capell\AccessGate\Contracts\AccessRequestMethod;
use Capell\AccessGate\Models\Area;

final class GitHubAccessRequestMethod implements AccessRequestMethod
{
    public function key(): string
    {
        return 'github';
    }

    public function label(): string
    {
        return __('app.access_gate.github');
    }

    public function description(): ?string
    {
        return __('app.access_gate.github_help');
    }

    public function isEnabled(Area $area): bool
    {
        return (bool) config('services.github.access_gate_enabled');
    }

    public function isPrimary(Area $area): bool
    {
        return true;
    }

    public function url(Area $area, ?string $requestedUrl = null): string
    {
        return route('app.access-gate.github.redirect', [
            'area' => $area->key,
            'redirect' => $requestedUrl,
        ]);
    }
}
```

Register the method in `access-gate.registration.identity_methods` or directly through `AccessRequestMethodRegistry`.

The Capell app should own OAuth state, provider callbacks, verified email lookup, GitHub username/profile metadata, and the decision to call `CreateRegistrationAction`, `ApproveRegistrationAction`, or `CreateAccessGateGrantAction`.

## Frontend Rules

When `capell-app/frontend` is installed, Access Gate registers these frontend rule conditions:

- `access_gate_has_active_grant`
- `access_gate_missing_active_grant`
- `access_gate_registration_status`
- `access_gate_area_status`

Rules fail closed when an area, condition, or registration cannot be resolved.
