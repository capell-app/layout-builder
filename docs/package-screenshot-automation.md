# Package Screenshot Automation

Deployment can generate package screenshots from the committed screenshot manifests. Each package owns a `packages/{package}/docs/screenshots.json` file, and the aggregate manifest is [package-screenshot-manifest.json](package-screenshot-manifest.json).

## How Deployment Should Use It

1. Install the package and its declared dependencies from `capell.json`.
2. If the screenshot manifest declares `composerRequires`, Composer require every listed package before seeding demo data. This is required for cross-package screenshots such as frontend authoring, where the editable page depends on core, admin, frontend, a theme beacon, and the authoring package.
3. Run migrations and package setup/demo commands listed in `docs/overview.md`.
4. Authenticate as an admin user with the required role or permission.
5. Resolve `admin-surface` targets through Filament resources or pages.
6. Resolve `frontend-url` targets through seeded demo routes or package route names.
7. Capture desktop and mobile screenshots. Use `scripts/capture-admin-screenshots.mjs` for admin surfaces when a manifest declares it, and keep `SCREENSHOT_FULL_PAGE=true` so long admin form builders are captured in full.
8. Execute any `browserTests` declared by the package manifest. These tests must run against the installed browser surface, not only server-rendered Blade.
9. Write files to `public/docs/screenshots/packages/{package}`.

## Manifest Contract

- `package`: package slug.
- `composerName`: Composer package name where available.
- `composerRequires`: optional list of Composer packages the screenshot/demo environment must require before capture.
- `outputDirectory`: deployment output path.
- `entries[].surface`: `admin` or `frontend`.
- `entries[].targetType`: `admin-surface` or `frontend-url`.
- `entries[].target`: resource/page class name when known, otherwise deployment resolves from seeded content.
- `entries[].docsPage`: optional markdown page where the screenshot is referenced.
- `entries[].output`: optional concrete output file when the package commits a docs screenshot or requires a stable filename.
- `browserTests`: optional browser scenario contracts the deployment runner must execute after package installation.
- `runner`: optional screenshot runner path, such as `scripts/capture-admin-screenshots.mjs`.
- `capture.fullPage`: when true, the runner should capture the full scrollable page instead of only the viewport.

## Notes

The package repo does not need to run a browser during docs generation. It commits the contract that the demo/docs deployment can consume after package installation.
