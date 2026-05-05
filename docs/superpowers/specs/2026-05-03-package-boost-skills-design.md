# Package Boost Skills Design

## Goal

Create very short Laravel Boost skills only where a Capell package has domain decisions, extension points, workflows, or AI/MCP relevance. Every package should still ship tiny shared Boost guidelines.

## Approach

Use the selective tiny skill approach.

Each selected package skill will contain:

- YAML front matter with a package-specific name and description.
- One sentence describing the package's purpose.
- A compact `Look` section with source path, namespace, and docs entry points.
- A compact `Rules` section with only package-relevant guardrails.

Shared Capell conventions stay in `resources/boost/guidelines/core.blade.php` and repository guidance. Thin packages should rely on guidelines only; do not create skills that repeat obvious package names and paths.

## Package Coverage

Every package under `packages/*` with a `composer.json` should have:

- `resources/boost/guidelines/core.blade.php`

Selected packages should also have:

- `resources/boost/skills/{skill-name}/SKILL.md`

Existing package Boost directories should be updated in place. Existing user work must not be reverted.

## Skill Allowlist

Ship package skills for:

- `address`
- `analytics`
- `assistant`
- `authentication-log`
- `migrator`
- `blog`
- `campaigns`
- `content-blocks`
- `default-theme`
- `deployments`
- `developer-tools`
- `forms`
- `mcp`
- `media-curator`
- `mosaic`
- `navigation`
- `redirects`
- `seo-tools`
- `site-search`
- `tags`
- `theme-studio-admin`
- `theme-studio-core`
- `workspaces`

Do not ship skills for packages where the skill would only restate a wrapper, middleware, renderer, metapackage, or beacon surface:

- `filament-peek`
- `html-minify`
- `theme-agency`
- `theme-corporate`
- `theme-saas`
- `theme-studio`
- `toolbar`

## Content Rules

- Keep each skill very short and focused.
- Aim for fast AI consumption over exhaustive documentation.
- Make each shipped skill unique to the package's purpose.
- Point agents to `README.md`, `docs/`, and `src/` instead of duplicating details.
- Mention package-specific extension points, safety concerns, and test command where useful.
- Do not add Composer dependencies.

## Verification

Run the Boost resource test after edits:

```bash
vendor/bin/pest tests/Packages/BoostResourcesTest.php
```

If edits are broad but documentation-only, do not run the full suite unless package discovery or test coverage files are changed.
