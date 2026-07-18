# Layout Builder

<!-- prettier-ignore-start -->

## What it does

Layout Builder assembles public pages from reusable Widgets arranged in named Layout containers. Widgets hold translated content, media, visibility dates, and presentation settings; a Layout controls where each widget appears. A single Layout can be shared by several pages, so a structural edit can have a wider effect than the page currently open.

## Installation and setup

Layout Builder requires Admin, Block Library, Core, and Frontend. `capell:layout-builder-install` publishes/runs the package migrations and publishes the admin assets. The application's package setup lifecycle then creates the widget blueprints, default widget catalogue, content types, and starter layouts; there is no separate `capell:layout-builder-setup` command. If the screens exist but standard widget types or layouts are missing, check that both install and package setup completed rather than creating replacement records by hand.

The default editor mode is Content first. Integrators can change it with `CAPELL_LAYOUT_BUILDER_DEFAULT_EDITOR_MODE=layout_first`; users are still limited to the mode their permissions allow. Other runtime options control lazy builder loading, preview layout matching, empty-widget rendering, and public widget snapshot retention.

## Screens and access

- **Websites -> Widgets** creates and edits reusable widget records and shows which layouts use them.
- **Websites -> Layouts** manages layout records, container/widget assignments, generated previews, and guided bulk changes.
- **Websites -> Layout Presets** is a list-only catalogue showing each preset's copy/linked mode, category, revision, and usage count. Create and insert presets from the Layout Builder itself.

Editors receive view and content-edit access by default. Structural layout changes, preset management, replication/deletion, reordering, and bulk mutation require the corresponding Layout permissions. The builder also checks the selected page, Layout, Site, and preset policy; an assigned-site administrator cannot apply a preset from another site. A global administrator may work across sites when their permissions allow it.

## Edit and save safely

Use **Content first** for widget content and assets without rearranging the page. Use **Layout first** to add, remove, move, resize, or configure containers and widget occurrences. Before changing a shared Layout, follow the pages-using-this-layout link and review the full impact.

Changes remain unsaved until **Save layout** succeeds. Undo and redo cover the current unsaved mutation history and are cleared after a successful save or reload. Saving is blocked when a Layout references an unknown widget; responsive-width diagnostics are warnings. If another editor saved the Layout after you opened it, your save is rejected and the builder reloads the current record. Reapply the intended change to the refreshed Layout instead of assuming your earlier state was stored.

Widget status and Visible from/Visible until dates are evaluated when public widget records are loaded; they do not need a publishing scheduler. A disabled, future, expired, inaccessible, untranslated, or empty widget may therefore be absent even though its key is still present in the Layout. Verify the exact Site, language, page, and theme after saving.

## Presets and linked updates

A copy preset is a site-scoped snapshot. The normal **Save as preset** flow stores layout structure and safe presentation metadata, not the widget's live editorial content, then inserts an independent copy later. Preset snapshots remove admin/editor identifiers, signed URLs, model identifiers, permissions, and other unsafe metadata before storage or use.

A linked preset keeps inserted containers connected to its source. Updating a linked source increments its revision and queues propagation to the other linked usages. Run a queue worker for this workflow. The sync job tries three times and serialises work per preset. It will not overwrite a Layout changed since its usage record was captured, remove a detached link, or replace a container when page-scoped assets would be orphaned; the run completes with conflicts instead.

The Layout Presets screen does not expose a conflict editor. Resolve the affected Layout or asset conflict, then queue recovery with `layout-builder:resync-preset <preset-id-or-key>`. Detach a container first when it should stop receiving linked changes.

## Guided bulk changes

The Layouts screen can preview moving, removing, swapping, or relocating widget occurrences across layouts filtered by Site, theme, group, key, status, and required widget. Review the stored preview before approval. Apply rechecks the Layout hash and skips records that changed after preview, producing a partial result instead of overwriting newer edits.

Runs targeting at least 50 Layouts or 250 pages are queued; smaller runs apply synchronously. A queue worker is therefore required for larger changes, and the queued user's **Bulk mutate layouts** permission is checked again when the job runs. Preview can block an ambiguous default-asset move. Removing a widget can either leave page-scoped assets unused with a warning or delete them during apply; a later Layout revert does not recreate deleted assets.

For scripted recovery, `capell:layouts:bulk-change` supports preview, approve/queue, and revert by run UUID. Revert only restores Layouts that still match the applied result; later edits are reported as drift and left untouched. Completed, blocked, and failed run snapshots are retained for 90 days by default.

## Public rendering and operations

Public rendering preloads enabled, in-window widgets and passes a restricted payload to Blade. Anonymous HTML must not contain authoring selectors, permissions, model identifiers, signed editor URLs, or other admin metadata. Interactive/lazy widget extensions use encrypted, Site/language-bound snapshots; saving or deleting the owning page rebuilds or revokes them. A missing or invalid snapshot fails closed for the interaction rather than revealing stored state.

Public fragment responses are cacheable for five minutes with a one-minute stale-while-revalidate window, omit session cookies, and are marked `noindex`. Allow for that cache when checking an urgent widget change.

Keep the Laravel scheduler running. It prunes expired/revoked public widget snapshots at 02:30 and terminal bulk-change runs daily, using overlap and single-server locks. Without it, normal page rendering continues but obsolete operational records accumulate.

Widget translations, layout/preset JSON, media relationships, user stamps, and activity history live in normal application storage; only public interaction snapshot payloads are encrypted by this package. Layout Builder does not register a Privacy Center exporter or eraser, so do not place subject-request data in generic widget fields without an owning export, retention, and erasure process.

---

For how to use Layout Builder, see the [admin guide](admin-guide.md).
For developers: see the [README](../README.md).

<!-- prettier-ignore-end -->
