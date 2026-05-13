# Form Builder

FormBuilder adds form definitions, encrypted submissions, frontend Livewire rendering, validation, and submission status handling to Capell.

## At A Glance

- Package: `capell-app/form-builder`
- Namespace: `Capell\FormBuilder\`
- Surfaces: Livewire, database
- Service providers: `packages/form-builder/src/Providers/FormBuilderServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/core`, `capell-app/frontend`

## What It Adds

FormBuilder adds form definitions, encrypted submissions, frontend Livewire rendering, validation, and submission status handling to Capell.

- Form and submission models.
- Frontend Livewire form component.
- Actions for validation, submission creation, archiving, read state, and spam marking.
- FormSubmitted event.
- Configurable submission storage and request metadata collection.

## Why It Matters

**For developers:** Keeps form schema, settings, submission payload, and metadata in data objects and casts so form handling remains typed across layers.

**For teams:** Lets editors collect responses from structured websites while keeping submission review inside the Capell admin workflow.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Frontend](https://github.com/capell-app/frontend)

**Open-source packages used here**

- No extra third-party Composer package beyond the Capell package stack is required here.

## Screens And Workflow

Screenshots are generated from [docs/screenshots.json](docs/screenshots.json) during package deployment.

- FormBuilder admin index.
- Create/edit form schema screen.
- Submissions index.
- Frontend form output.
- Submission detail view.

## Technical Shape

- FormBuilderServiceProvider registers the package.
- Config file: capell-form-builder.php.
- Migrations create form-builder and submissions.
- Models: Form and Submission.
- Livewire component: FormComponent.
- EncryptedDataCast protects stored submission data.

## Code Map

| Area      | Path                                  | Purpose                                                             |
| --------- | ------------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/form-builder/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/form-builder/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/form-builder/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/form-builder/src/Models`    | Eloquent records owned by the package.                              |
| Livewire  | `packages/form-builder/src/Livewire`  | Interactive frontend or admin components.                           |
| Providers | `packages/form-builder/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/form-builder/resources`     | Views, translations, assets, and package resources.                 |
| Config    | `packages/form-builder/config`        | Package configuration and publishable config.                       |
| Database  | `packages/form-builder/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/form-builder/tests`         | Package-level Pest coverage.                                        |

## Runtime Surface

- Livewire: `FormComponent`.

## Data And Persistence

- form-builder belongs to sites and stores handle, schema, settings, and active state.
- submissions belongs to form-builder and sites and stores payload, meta, status, and submitted_at.
- Submission payload and metadata are represented by data objects.
- Deletion and retention rules should be verified against the host application policy.

- Models: `Form`, `Submission`.
- Migrations: `2026_05_10_190849_01_create_form-builder_table.php`, `2026_05_10_190849_02_create_submissions_table.php`.
- Config: `packages/form-builder/config/capell-form-builder.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Events: `FormSubmitted`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds form-builder and submissions tables.
- Adds frontend Livewire form component.
- Adds config keys for storing submissions, IP address collection, and user agent collection.
- No routes are visible in this package.

## Install And Setup

- Install with `composer require capell-app/form-builder` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Admin And Access

- None proven in this package directory.

- None proven in this package directory.

## Common Pitfalls

- Disable IP/user agent collection where privacy policy requires it.
- Run migrations before rendering form components.
- Validate field schema before accepting public submissions.

## Docs

- [credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
- [overview.md](docs/overview.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/form-builder/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Treat public routes as untrusted input and keep validation, permission checks, and side effects inside actions or dedicated services.
- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
