# Translation Manager Overview

Translation Manager is a Capell admin package for managing Laravel language files from Filament.

The package is file-first. App language files are editable in place. Package and vendor files are treated as read-only source material unless explicitly configured otherwise; edits are written to Laravel override paths so package upgrades remain safe.

Phase one does not create Capell language records, database tables, jobs, or frontend output.
