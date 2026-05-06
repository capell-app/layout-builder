# Admin screenshots

Screenshots of the approval-workflow UI captured from a local Capell admin panel.
These are embedded in [page-creation-and-approval-flow.md](../../page-creation-and-approval-flow.md).

## Files

| File                               | What it shows                                                                                  |
| ---------------------------------- | ---------------------------------------------------------------------------------------------- |
| `01-pages-list.png`                | Pages list with SEO Overview summary and the table of pages                                    |
| `04-create-page-save-as-draft.png` | `/admin/pages/create` form with `Create` + `Save as Draft` + `Create & create another` buttons |
| `05-publishing-studio-list.png`           | PublishingStudio list with status badges + `Latest review note` column + action buttons per row      |

## Regenerating

**Prerequisites:**

- A Capell admin panel running locally (e.g. `http://capell-ruby.local/admin/`)
- Admin user credentials
- The host app's `AdminPanelProvider` registering `WorkspaceResource` (otherwise workspace screenshots 05–07 aren't reachable — add `->resources([WorkspaceResource::class])` to the panel)

**Simple manual capture (recommended):**

1. Log into the admin panel in Chrome.
2. Use macOS `Cmd+Shift+4` / `Cmd+Shift+5` (or your OS equivalent) to capture each view listed above.
3. Save each PNG into this directory using the filenames above.

**Scripted capture with Playwright:**

A ready-to-run script lives in the `capell` host repo at `scripts/capture-admin-screenshots.mjs`.

```bash
npm install --save-dev playwright
npx playwright install chromium

ADMIN_URL=http://capell-ruby.local/admin \
ADMIN_EMAIL=you@example.com \
ADMIN_PASSWORD=secret \
node scripts/capture-admin-screenshots.mjs
```

The script logs in, captures the documented views, and writes them into this directory. Workspace-specific screens are skipped with a warning if the host panel doesn't register `WorkspaceResource`.
