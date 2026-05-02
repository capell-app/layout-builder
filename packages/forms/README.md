# Capell Forms

**Product group:** Capell Forms
**Tier:** Premium

Forms adds form and submission models for Capell projects that need editor-managed contact forms, enquiries, lead capture, or simple content forms.

## When to install it

Install Forms when the site needs submissions stored inside the Capell/Laravel app instead of sending users to a third-party form tool.

## Quick install

```bash
composer require capell-app/forms
php artisan migrate
php artisan optimize:clear
```

## What appears in the admin

| Area        | What editors can do                                |
| ----------- | -------------------------------------------------- |
| Forms       | Manage form definitions when resources are enabled |
| Submissions | Review submitted form data                         |

## What developers get

- `Form` and `Submission` models.
- Laravel-native storage for user submissions.
- A foundation for custom Filament resources, notifications, and frontend widgets.
