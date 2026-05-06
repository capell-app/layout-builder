# WordPress Importer

WordPress Importer adds WordPress WXR XML parsing to the Capell Migration AIOrchestrator.

It is intentionally separate from `capell-app/migration-assistant`: MigrationAssistant owns the ai-orchestrator workflow, mapping, preview, validation, execution state, and rollback dashboard-dashboard_reports; this package owns the WordPress export reader.

## What It Reads

- WordPress WXR posts and pages.
- Title, slug, link, content, excerpt, status, date, parent id, and author login.
- Category and tag metadata.
- Attachment URL references where the export includes them.

The reader returns MigrationAssistant's neutral source shape so the ai-orchestrator can map the rows to Capell pages or types, preview changes, validate the plan, execute the import, and produce the rollback report.

## Built With

This package makes its Composer dependencies visible because they are part of the value proposition, not just plumbing. When an upstream package has a public repository, its linked preview card points readers back to the maintainers so their work gets proper credit.

**Capell packages used here**

- [Capell Admin](https://github.com/capell-app/admin)
- [Capell Core](https://github.com/capell-app/core)
- [Capell Migration Assistant](../migration-assistant/README.md)

**Open-source packages used here**

- [PHP SimpleXML extension](https://www.php.net/manual/en/book.simplexml.php) - the PHP XML reader used to parse WordPress WXR export files.
- [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) - Laravel package bootstrapping for config, migrations, commands, translations, and service provider setup.

**Linked package previews**

[![Spatie Laravel Package Tools GitHub preview](https://opengraph.githubassets.com/capell-readme/spatie/laravel-package-tools)](https://github.com/spatie/laravel-package-tools)

## Install Impact

- Requires `capell-app/migration-assistant`, `capell-app/core`, `capell-app/admin`, and `ext-simplexml`.
- Registers a WordPress WXR source reader with MigrationAssistant.
- Does not add migrations; MigrationAssistant owns import sessions and rollback dashboard-dashboard_reports.

## Package Docs

- [docs/credits-and-acknowledgements.md](docs/credits-and-acknowledgements.md)
