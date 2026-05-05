# WordPress Importer

WordPress Importer adds WordPress WXR XML parsing to the Capell Migration Assistant.

It is intentionally separate from `capell-app/migrator`: Migrator owns the assistant workflow, mapping, preview, validation, execution state, and rollback reports; this package owns the WordPress export reader.

## What It Reads

- WordPress WXR posts and pages.
- Title, slug, link, content, excerpt, status, date, parent id, and author login.
- Category and tag metadata.
- Attachment URL references where the export includes them.

The reader returns Migrator's neutral source shape so the assistant can map the rows to Capell pages or types, preview changes, validate the plan, execute the import, and produce the rollback report.

## Install Impact

- Requires `capell-app/migrator`, `capell-app/core`, `capell-app/admin`, and `ext-simplexml`.
- Registers a WordPress WXR source reader with Migrator.
- Does not add migrations; Migrator owns import sessions and rollback reports.
