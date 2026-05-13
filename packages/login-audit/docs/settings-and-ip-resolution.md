# Settings And IP Resolution

Login Audit wraps `rappasoft/laravel-authentication-log`, stores Capell-owned audit rows, adds settings, and registers a frontend activity middleware alias.

## Runtime Surface

| Surface              | Code                                                        |
| -------------------- | ----------------------------------------------------------- |
| Model                | `Capell\LoginAudit\Models\LoginAudit`                       |
| Observer             | `LoginAuditObserver` on the vendor authentication log model |
| Middleware alias     | `frontend.activity`                                         |
| Settings group       | `login_audit`                                               |
| Protected table      | `login-audit.table_name`, default `login_audit`             |
| Admin bridge         | `LoginAuditAdminBridge`                                     |
| User schema extender | `LoginAuditUserSchemaExtender`                              |

## Config Keys

| Key                                              | Use                                          |
| ------------------------------------------------ | -------------------------------------------- |
| `login-audit.table_name`                         | Audit table name.                            |
| `login-audit.db_connection`                      | Optional database connection for audit rows. |
| `login-audit.events`                             | Auth events the package listens to.          |
| `login-audit.listeners`                          | Listener classes for those events.           |
| `login-audit.notifications.new-device.enabled`   | Sends new-device notifications.              |
| `login-audit.notifications.failed-login.enabled` | Sends failed-login notifications.            |
| `login-audit.purge`                              | Retention window used by cleanup.            |
| `login-audit.behind_cdn`                         | CDN IP header config, or `false`.            |

## CDN IP Resolution

`ResolveLoginAuditIpAddressAction` returns null when IP tracking is disabled by settings. When `login-audit.behind_cdn` is configured, it reads the configured server header instead of `Request::ip()`.

```php
// config/login-audit.php
'behind_cdn' => [
    'http_header_field' => 'HTTP_CF_CONNECTING_IP',
],
```

Only set this when the application is actually behind a trusted CDN or proxy. A spoofable header is worse than no IP logging.

## Verification

```bash
vendor/bin/pest packages/login-audit/tests --configuration=phpunit.xml
```
