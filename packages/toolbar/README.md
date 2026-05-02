# Capell Frontend Toolbar

**Product group:** Capell Foundation
**Tier:** Free

Frontend Toolbar adds the bridge between the rendered site and the Capell admin. It gives authenticated editors a way to jump from frontend pages back into the admin workflow.

## When to install it

Install Frontend Toolbar when editors need a visible frontend editing affordance, preview beacon, or admin shortcut while reviewing public pages.

## Quick install

```bash
composer require capell-app/frontend-toolbar
php artisan optimize:clear
php artisan capell:static-site
```

## What appears in the admin

Nothing new appears as a standalone admin resource. The toolbar is frontend-facing and depends on a theme or render hook that outputs it.

## What developers get

- Toolbar service provider.
- Beacon request and controller.
- Frontend integration points for authenticated editor tooling.
