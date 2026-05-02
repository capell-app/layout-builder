# Capell Authentication Log

**Product group:** Capell Operations
**Tier:** Premium

Authentication Log adds login and activity visibility to Capell admin dashboards using the Tapp Filament Authentication Log package.

## When to install it

Install Authentication Log when administrators need to see recent admin logins, account activity, and security-relevant user events.

## Quick install

```bash
composer require capell-app/authentication-log
php artisan migrate
php artisan optimize:clear
```

## What appears in the admin

| Area                  | What administrators can see                     |
| --------------------- | ----------------------------------------------- |
| Dashboard             | Recent authentication activity widget           |
| User/account activity | Login records, timestamps, and related metadata |

## What developers get

- `AuthenticationLog` model.
- Dashboard widget for recent authentication events.
- Admin and user activity middleware.
- Query action and observer for consistent log handling.
