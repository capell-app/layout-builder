# Package Testing Audit

## layout-builder

- [ ] Installs cleanly in a fresh Capell demo workbench
- [ ] Declared package dependencies are correct
- [ ] Migrations run cleanly and are idempotent
- [ ] Service provider boots without duplicate registry keys
- [ ] Filament resources, pages, widgets, settings, actions, and relation managers load
- [ ] Frontend routes, render hooks, widgets, themes, or Blade views work where the package exposes them
- [ ] Custom package functionality works through realistic user flows
- [ ] Permissions, roles, workspace boundaries, and draft/publish behaviour are correct where applicable
- [ ] Caching, queues, scheduled jobs, webhooks, commands, and external integrations degrade safely
- [ ] Package Pest suite is meaningful, not just smoke coverage
- [ ] Coverage gap list exists for untested Actions, Data objects, Enums, services, policies, resources, and frontend render paths
- [ ] `README.md` and package docs match the real install and usage flow
- [ ] `packages/layout-builder/docs/screenshots.json` covers the important admin and frontend surfaces
- [ ] Generated screenshots are clear, seeded with useful data, and not hiding broken UI states
- [ ] Known risks and follow-up issues are recorded

## blog

- [x] Installs cleanly in a fresh Capell demo workbench
- [x] Declared package dependencies are correct
- [x] Migrations run cleanly and are idempotent
- [x] Service provider boots without duplicate registry keys
- [x] Filament resources, pages, widgets, settings, actions, and relation managers load
- [x] Frontend routes, render hooks, widgets, themes, or Blade views work where the package exposes them
- [x] Custom package functionality works through realistic user flows
- [x] Permissions, roles, workspace boundaries, and draft/publish behaviour are correct where applicable
- [x] Caching, queues, scheduled jobs, webhooks, commands, and external integrations degrade safely
- [x] Package Pest suite is meaningful, not just smoke coverage
- [x] Coverage gap list exists for untested Actions, Data objects, Enums, services, policies, resources, and frontend render paths
- [x] `README.md` and package docs match the real install and usage flow
- [x] `packages/blog/docs/screenshots.json` covers the important admin and frontend surfaces
- [x] Generated screenshots are clear, seeded with useful data, and not hiding broken UI states
- [x] Known risks and follow-up issues are recorded
- Blog test suite passed cleanly: Arch, Filament resources, widgets, static-site behavior, and integration command coverage all green.
- Blog dependencies are explicit in `capell.json` and include `layout-builder`, `navigation`, `tags`, `insights`, `frontend`, `admin`, and `core`.

## address

- [x] Installs cleanly in a fresh Capell demo workbench
- [x] Declared package dependencies are correct
- [x] Migrations run cleanly and are idempotent
- [x] Service provider boots without duplicate registry keys
- [x] Filament resources, pages, widgets, settings, actions, and relation managers load
- [x] Frontend routes, render hooks, widgets, themes, or Blade views work where the package exposes them
- [x] Custom package functionality works through realistic user flows
- [x] Permissions, roles, workspace boundaries, and draft/publish behaviour are correct where applicable
- [x] Caching, queues, scheduled jobs, webhooks, commands, and external integrations degrade safely
- [x] Package Pest suite is meaningful, not just smoke coverage
- [x] Coverage gap list exists for untested Actions, Data objects, Enums, services, policies, resources, and frontend render paths
- [x] `README.md` and package docs match the real install and usage flow
- [x] `packages/address/docs/screenshots.json` covers the important admin and frontend surfaces
- [x] Generated screenshots are clear, seeded with useful data, and not hiding broken UI states
- [x] Known risks and follow-up issues are recorded
- Address test suite passed cleanly with 81 passing tests and 277 assertions, including resource, model, observer, command, and flag-render coverage.
- `packages/address/docs/screenshots.json` is present and should be kept in sync with the country/address admin surfaces.

## ai-orchestrator

- [x] Installs cleanly in a fresh Capell demo workbench
- [x] Declared package dependencies are correct
- [x] Migrations run cleanly and are idempotent
- [x] Service provider boots without duplicate registry keys
- [x] Filament resources, pages, widgets, settings, actions, and relation managers load
- [x] Frontend routes, render hooks, widgets, themes, or Blade views work where the package exposes them
- [x] Custom package functionality works through realistic user flows
- [x] Permissions, roles, workspace boundaries, and draft/publish behaviour are correct where applicable
- [x] Caching, queues, scheduled jobs, webhooks, commands, and external integrations degrade safely
- [x] Package Pest suite is meaningful, not just smoke coverage
- [x] Coverage gap list exists for untested Actions, Data objects, Enums, services, policies, resources, and frontend render paths
- [x] `README.md` and package docs match the real install and usage flow
- [x] `packages/ai-orchestrator/docs/screenshots.json` covers the important admin and frontend surfaces
- [x] Generated screenshots are clear, seeded with useful data, and not hiding broken UI states
- [x] Known risks and follow-up issues are recorded
- AIOrchestrator test suite passed cleanly with boundary, capability, and registry coverage in place.
- The package surface stays isolated from LayoutBuilder, which matches the package boundary rule.

## insights

- [x] Installs cleanly in a fresh Capell demo workbench
- [x] Declared package dependencies are correct
- [x] Migrations run cleanly and are idempotent
- [x] Service provider boots without duplicate registry keys
- [x] Filament resources, pages, widgets, settings, actions, and relation managers load
- [x] Frontend routes, render hooks, widgets, themes, or Blade views work where the package exposes them
- [x] Custom package functionality works through realistic user flows
- [x] Permissions, roles, workspace boundaries, and draft/publish behaviour are correct where applicable
- [x] Caching, queues, scheduled jobs, webhooks, commands, and external integrations degrade safely
- [x] Package Pest suite is meaningful, not just smoke coverage
- [x] Coverage gap list exists for untested Actions, Data objects, Enums, services, policies, resources, and frontend render paths
- [x] `README.md` and package docs match the real install and usage flow
- [x] `packages/insights/docs/screenshots.json` covers the important admin and frontend surfaces
- [x] Generated screenshots are clear, seeded with useful data, and not hiding broken UI states
- [x] Known risks and follow-up issues are recorded
- Insights test suite passed cleanly with 77 tests and 382 assertions, including consent, beacon, frontend script, settings, provider, retention, and widget coverage.
- Screenshot manifest is present for the core admin and frontend surfaces called out in the README.

## current audit notes

- `deployments` now has a Capell manifest and screenshot manifest, so it should be included in the normal manifest-backed package audit flow.
- `agent-bridge` has `docs/screenshots.json`, but its manifest entries still need composer name cleanup where `composerName` is null.
- `packages/foundation-theme/` is the single canonical `capell-app/foundation-theme` package after consolidating the old compatibility resources.
- `frontend-authoring` is using `capell-app/frontend-authoring` in screenshot metadata, which is worth keeping consistent while the manifest is audited.
