# Access Requests

Access Gate protects a named area, accepts public registration requests, grants access to users or email addresses, and can issue browser tokens for guest access.

## Request Flow

1. A public route or `SubmitAccessGatePublicAction` passes an area key and email address to `CreateRegistrationAction`.
2. The action validates built-in fields and any registered `RegistrationField` implementations.
3. Existing approved or claimed registrations for the same email can receive a new claim token instead of creating duplicates.
4. Areas using `first_n_auto_approve` can approve the next pending registration automatically.
5. Approved registrations receive a claim token. Claiming the token creates or reuses a grant and can set the browser token cookie.

`ResolveAccessGateAccessAction` is the read side. It checks area status, schedule, site scope, authenticated grants, email grants, and browser tokens.

## Public Routes

| Route                         | Purpose                                                                      |
| ----------------------------- | ---------------------------------------------------------------------------- |
| `GET /access/request/{area}`  | Shows the public request form.                                               |
| `POST /access/request/{area}` | Stores a registration request.                                               |
| `GET /access/claim/{token}`   | Claims an emailed token.                                                     |
| `POST /access/logout/{area}`  | Revokes the current browser token for an area.                               |
| `GET /access/status/{area}`   | Optional status endpoint when `access-gate.status_endpoint_enabled` is true. |

The route prefix comes from `access-gate.route_prefix`.

## Register A Request Method

Request methods decide how a visitor asks for access. They implement `AccessRequestMethod` and are stored in `AccessRequestMethodRegistry`.

The example translation namespace is the host app's namespace. Use the package namespace that owns your request method.

```php
use Capell\AccessGate\Contracts\AccessRequestMethod;
use Capell\AccessGate\Models\Area;

final class EmailAccessRequestMethod implements AccessRequestMethod
{
    public function key(): string
    {
        return 'email';
    }

    public function label(): string
    {
        return __('host-app::access_gate.provider_login');
    }

    public function description(): ?string
    {
        return null;
    }

    public function isEnabled(Area $area): bool
    {
        return true;
    }

    public function isPrimary(Area $area): bool
    {
        return true;
    }

    public function url(Area $area, ?string $requestedUrl = null): string
    {
        return route('capell-access-gate.request', [
            'area' => $area,
            'requested_url' => $requestedUrl,
        ]);
    }
}
```

Register it from a service provider:

```php
use Capell\AccessGate\Support\AccessRequestMethodRegistry;

$this->app->afterResolving(
    AccessRequestMethodRegistry::class,
    static function (AccessRequestMethodRegistry $registry): void {
        $registry->register(EmailAccessRequestMethod::class);
    },
);
```

## Add Registration Fields

Custom fields implement `RegistrationField`. The return value is stored in `field_values`.

Use the package namespace that owns the field label; the value is shown on the public request form.

```php
use Capell\AccessGate\Contracts\RegistrationField;
use Capell\AccessGate\Data\RegistrationFieldValue;
use Illuminate\Support\Facades\Validator;

final class CompanyRegistrationField implements RegistrationField
{
    public function key(): string
    {
        return 'company';
    }

    public function label(): string
    {
        return __('host-app::access_gate.company');
    }

    public function validate(array $input): RegistrationFieldValue
    {
        $validated = Validator::make($input, [
            'company' => ['required', 'string', 'max:120'],
        ])->validate();

        return new RegistrationFieldValue('company', $validated['company']);
    }
}
```

Register it with `RegistrationFieldRegistry`:

```php
use Capell\AccessGate\Support\RegistrationFieldRegistry;

$this->app->afterResolving(
    RegistrationFieldRegistry::class,
    static function (RegistrationFieldRegistry $registry): void {
        $registry->register(CompanyRegistrationField::class);
    },
);
```

## Public Actions Integration

Access Gate can receive requests through Public Actions by using `SubmitAccessGatePublicAction`. The payload must include:

```json
{
    "area": "capell-preview",
    "email": "person@example.test",
    "requested_url": "https://example.test/preview"
}
```

The handler strips `area` and `user_id`, merges submission metadata, and calls `CreateRegistrationAction`.

## Configuration

| Key                                         | Purpose                                                                         |
| ------------------------------------------- | ------------------------------------------------------------------------------- |
| `access-gate.connection`                    | Optional database connection.                                                   |
| `access-gate.route_prefix`                  | Public route prefix.                                                            |
| `access-gate.status_endpoint_enabled`       | Enables the public status route.                                                |
| `access-gate.claim_token_ttl_minutes`       | Claim token lifetime.                                                           |
| `access-gate.cookies.browser_token.*`       | Browser token cookie name, lifetime, domain, secure flag, and SameSite policy.  |
| `access-gate.middleware.default`            | Middleware used by public routes.                                               |
| `access-gate.middleware.page_cache_aliases` | Middleware aliases stripped when gated pages must not be cached as public HTML. |
| `access-gate.registration.methods`          | Built-in request method configuration.                                          |
| `access-gate.registration.identity_methods` | Optional identity method configuration.                                         |
| `access-gate.registration.fields`           | Optional registration field configuration.                                      |
| `access-gate.install.default_area.*`        | Defaults used by the setup/install commands.                                    |

## Commands

```text
capell:access-gate-install
capell:access-gate-setup
capell:access-gate-doctor
```

Use `capell:access-gate-doctor` when a gated route behaves like a public cached page, or when claim/browser tokens fail unexpectedly.

## Cache And Gated Pages

Do not let a denied or personalized gated response be written as anonymous HTML. Keep the page-cache middleware aliases in `access-gate.middleware.page_cache_aliases` current when the host app changes cache middleware names.
