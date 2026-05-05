# Package Screenshot Automation

Deployment can generate package screenshots from the committed screenshot manifests. Each package owns a `packages/{package}/docs/screenshots.json` file, and the aggregate manifest is [package-screenshot-manifest.json](package-screenshot-manifest.json).

## How Deployment Should Use It

1. Install the package and its declared dependencies from `capell.json`.
2. Run migrations and package setup/demo commands listed in `docs/overview.md`.
3. Authenticate as an admin user with the required role or permission.
4. Resolve `admin-surface` targets through Filament resources or pages.
5. Resolve `frontend-url` targets through seeded demo routes or package route names.
6. Capture desktop and mobile screenshots. Use `scripts/capture-admin-screenshots.mjs` for admin surfaces when a manifest declares it, and keep `SCREENSHOT_FULL_PAGE=true` so long admin forms are captured in full.
7. Write files to `public/docs/screenshots/packages/{package}`.

## Manifest Contract

- `package`: package slug.
- `composerName`: Composer package name where available.
- `outputDirectory`: deployment output path.
- `entries[].surface`: `admin` or `frontend`.
- `entries[].targetType`: `admin-surface` or `frontend-url`.
- `entries[].target`: resource/page class name when known, otherwise deployment resolves from seeded content.
- `runner`: optional screenshot runner path, such as `scripts/capture-admin-screenshots.mjs`.
- `capture.fullPage`: when true, the runner should capture the full scrollable page instead of only the viewport.

## Notes

The package repo does not need to run a browser during docs generation. It commits the contract that the demo/docs deployment can consume after package installation.
