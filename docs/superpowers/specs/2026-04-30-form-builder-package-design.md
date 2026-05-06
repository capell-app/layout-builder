# FormBuilder Package Design

## Goal

Build `capell-app/form-builder` into a small Capell package for editor-managed form-builder, frontend submissions, and an admin submission inbox.

The package should solve the common site needs first: contact form-builder, enquiry form-builder, newsletter interest form-builder, lead capture form-builder, and simple content intake. It should not become a full form-builder platform in its first version.

## Name

Keep the package name as **FormBuilder**:

- Composer package: `capell-app/form-builder`
- Namespace: `Capell\FormBuilder`
- Package directory: `packages/form-builder`
- Admin navigation group: `FormBuilder`
- Submission resource label: `Inbox`

`FormBuilder` is short, obvious, and already matches the existing package stub. `Inbox` is a better editor-facing name for reviewing submissions than `Submissions`.

## Scope

Version 1 includes:

- Editor-managed form definitions.
- A compact set of standard field types.
- Frontend rendering through a package Blade or Livewire component.
- Server-side validation.
- Stored submissions.
- Admin review, read, archive, and spam workflows.
- Events for notifications and project-specific integrations.
- Published/overridable views for frontend markup.

Version 1 excludes:

- File uploads.
- Multi-step form-builder.
- Conditional logic.
- Payment form-builder.
- Calculated fields.
- Public form insights.
- Drag-and-drop layout controls beyond a simple field ordering UI.
- Built-in CRM, email marketing, or webhook delivery.
- LayoutBuilder widgets and newsletter-specific behavior inside the core FormBuilder package.

Those exclusions keep the package focused and make future additions deliberate.

## Dependent Packages

FormBuilder should support dependent packages that need a submission pipeline without forcing those features into the core package.

The first dependent package should be **Newsletter**:

- Composer package: `capell-app/newsletter`
- Namespace: `Capell\Newsletter`
- Package directory: `packages/newsletter`
- Depends on: `capell-app/form-builder` and `capell-app/layout-builder`

Newsletter owns the page-builder experience for newsletter signup:

- Registers a LayoutBuilder widget for newsletter signup.
- Creates or resolves a default `newsletter-signup` form.
- Renders the signup widget through LayoutBuilder while submitting through FormBuilder.
- Stores submissions in the FormBuilder inbox.
- Adds newsletter-specific metadata such as source widget key, page URL, and optional audience/tag values.

Newsletter should not ship email marketing provider integrations in version 1. It can dispatch a newsletter-specific event after a successful FormBuilder submission so projects can connect Mailchimp, Campaign Monitor, HubSpot, or another provider in application code.

This establishes the package pattern for future LayoutBuilder-dependent form experiences: keep FormBuilder generic, and put specialised widgets in separate packages that depend on FormBuilder and LayoutBuilder.

## Architecture

The existing `packages/form-builder` stub remains the base. It should be expanded rather than renamed.

Domain behavior belongs in actions:

- `CreateSubmissionAction` validates and stores a submission.
- `MarkSubmissionReadAction` marks a submission as read.
- `ArchiveSubmissionAction` archives a submission.
- `MarkSubmissionSpamAction` marks a submission as spam.

Structured data belongs in data objects:

- `FormSchemaData` represents the full field list.
- `FormFieldData` represents one field definition.
- `FormSettingsData` represents success copy, storage, notification target, and spam options.
- `SubmissionPayloadData` wraps submitted field values.
- `SubmissionMetaData` stores request context such as IP, user agent, URL, and workspace or site context where available.

Persisted state belongs in enums:

- `SubmissionStatus` with `New`, `Read`, `Archived`, and `Spam`.
- `FormFieldType` with only the supported v1 field types.

Frontend rendering should stay replaceable. The package ships a default component, but projects can override views or subscribe to events without replacing the submission action.

## Field Types

Version 1 field types:

- Text
- Email
- Textarea
- Select
- Checkbox
- Hidden
- Honeypot

Each field supports:

- `key`
- `label`
- `type`
- `required`
- `placeholder`
- `helpText`
- `options` for select and checkbox fields
- `defaultValue`
- `validationRules`

Validation rules should be stored as a small allow-listed set, not arbitrary PHP closures or raw executable behavior.

## Database

`form-builder` should contain:

- `id`
- `site_id`
- `name`
- `handle`
- `description`
- `schema`
- `settings`
- `is_active`
- timestamps

`submissions` should contain:

- `id`
- `form_id`
- `site_id`
- `payload`
- `meta`
- `status`
- `submitted_at`
- timestamps

Indexes:

- `form-builder.site_id`
- unique `form-builder.site_id, form-builder.handle`
- `submissions.form_id`
- `submissions.site_id`
- `submissions.status`
- `submissions.submitted_at`

The package already has `form-builder` and `submissions` migrations. They should be adjusted in place while the package is still unreleased.

FormBuilder are site-scoped in v1. This keeps handle lookups simple and lets the database enforce `site_id, handle` uniqueness without nullable-index edge cases.

## Admin

Add a `FormResource` for managing form definitions:

- Name
- Handle
- Description
- Active state
- Field builder
- Success message
- Optional notification email
- Store submissions toggle

Add a `SubmissionResource` labelled `Inbox`:

- Table columns for form, status, submitted date, and a compact payload preview.
- Filters for form and status.
- View page for the full payload and metadata.
- Actions for mark read, archive, and mark spam.

Submissions should be read-only by default. Editing submitted payloads would make audit trails less trustworthy and is not needed for the simple version.

## Frontend

Ship one primary render path:

```blade
<x-capell-form-builder::form handle="contact" />
```

The Blade component resolves the active form by handle and site context, then mounts a package Livewire component for rendering fields and handling submission. This follows the existing package pattern used by Blog and LayoutBuilder, keeps frontend behavior testable, and avoids adding a separate public route surface for version 1.

Submission flow:

1. Resolve the form by site and handle.
2. Reject inactive or missing form-builder.
3. Build validation rules from `FormSchemaData`.
4. Check honeypot fields when present.
5. Run `CreateSubmissionAction`.
6. Dispatch `FormSubmitted`.
7. Show the configured success message.

## Customisation

Keep customisation small but useful:

- Published frontend views for field markup.
- Config for whether submissions are stored by default.
- Config for spam metadata collection.
- `FormSubmitted` event for email, CRM, Slack, or webhook integrations.
- A field type registry only if a second package or project needs to add fields during implementation.

Do not add a broad plugin API in version 1. Events, views, data objects, and actions are enough.

## Testing

Tests should focus on behavior rather than HTTP-heavy flows:

- Action tests for creating submissions.
- Unit tests for schema-to-validation-rule generation.
- Model tests for casts and relationships.
- Filament resource tests for basic visibility and actions.
- Frontend component tests for rendering active form-builder and handling successful submissions.
- Arch tests to preserve package boundaries.

Single package command:

```bash
vendor/bin/pest packages/form-builder/tests
```

## Implementation Sequence

1. Update migrations and models for handles, schema, settings, payload, meta, status, and site scoping.
2. Add data objects and enums.
3. Add factories and focused model tests.
4. Add `CreateSubmissionAction` and validation building.
5. Add submission status actions.
6. Add `FormSubmitted` event.
7. Add Filament resources for form-builder and inbox.
8. Add frontend rendering and submission handling.
9. Add package translations and docs.
10. Add the separate Newsletter package with a LayoutBuilder newsletter signup widget.

This order keeps the model and action layer stable before the admin and frontend surfaces are added.

## Deferred Work

Defer custom field registration unless implementation exposes a real need. The default field set is enough for the first usable package.
