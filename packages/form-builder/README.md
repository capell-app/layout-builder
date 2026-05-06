# FormBuilder

Status: **Available, schema-owning** · Kind: **package** · Tier: **premium** · Bundle: **form-builder** · Contexts: **admin, frontend** · Product group: **Capell FormBuilder**

## What This Plugin Adds

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

## Data Model

- form-builder belongs to sites and stores handle, schema, settings, and active state.
- submissions belongs to form-builder and sites and stores payload, meta, status, and submitted_at.
- Submission payload and metadata are represented by data objects.
- Deletion and retention rules should be verified against the host application policy.

## Install Impact

- Adds form-builder and submissions tables.
- Adds frontend Livewire form component.
- Adds config keys for storing submissions, IP address collection, and user agent collection.
- No routes are visible in this package.

## Commands

- None proven in this package directory.

## Admin And Access

- None proven in this package directory.

- None proven in this package directory.

## Common Pitfalls

- Disable IP/user agent collection where privacy policy requires it.
- Run migrations before rendering form components.
- Validate field schema before accepting public submissions.

## Quick Start

1. Install the package with `composer require capell-app/form-builder`.
2. Run the package migrations or the Capell package installer required by the host app.
3. Open the new admin or frontend surface and verify the result.

## Next Steps

- [docs/overview.md](docs/overview.md)
- [../campaign-studio/README.md](../campaign-studio/README.md)
- [../insights/README.md](../insights/README.md)
- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
