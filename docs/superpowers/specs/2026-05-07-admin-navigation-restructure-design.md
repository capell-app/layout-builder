## Admin Navigation Restructure Design

### Goal

Reduce top-level navigation clutter in the Capell admin, make the menu easier for editors to scan, and fix the Events group icon issue.

### Current Problems

- Too many top-level groups.
- Mixed patterns: some items are standalone, some are grouped.
- Content-related items are split across `Pages`, `Articles`, `Content`, `Contents`, and `Events`.
- The `Events` group label is visible but its icon is not rendering in the sidebar.

### Approved Target Structure

- `Dashboard` stays as a top-level item.
- `Content`
    - Pages
    - Articles
    - Events
    - Media
    - Tags
    - Sections
    - Content Scheduler
    - Scheduled Publishing
    - Preview Links
- `Marketing`
    - Campaign Studio resources
    - Newsletter resources
- `Site`
    - Sites
    - Languages
    - Types
    - Navigations
    - Layouts
    - Widgets
    - Themes
    - Redirects
- `Insights`
    - GA4
    - Insights
    - SEO pages
- `System`
    - Monitoring
    - Diagnostics
    - Permissions
    - Deployments
    - Imports
    - Login audit
    - Other admin-only utilities

### Implementation Shape

#### Events icon fix

The current group registration icon is not surfacing in the sidebar UI. The implementation should verify how the admin panel consumes group icons and then align the Events group with the same pattern used by other visible groups.

Audit note: the likely seam is not the Events package declaration itself. `EventsServiceProvider::registerNavigationGroups()` registers the icon on `CapellAdmin`, but the admin panel currently snapshots `CapellAdmin::getNavigationGroups()` during `AdminPanelProvider::panel()` before installed package admin providers are registered by `CapellAdminPlugin`. That means Filament can still build an `Events` group from navigation items while losing the registered group icon metadata.

The fix should be applied at that integration point rather than by adding more icon declarations in the Events package.

#### Navigation regrouping

Update the navigation group returned by each relevant resource/page so the admin IA matches the approved structure.

This should be done with narrow, package-local changes:

- content-facing packages move into `Content`
- structure/building packages move into `Site`
- campaign/newsletter packages move into `Marketing`
- reporting/analytics/SEO packages move into `Insights`
- admin/ops packages move into `System`

Avoid unrelated renames or resource behavior changes.

### Constraints

- Keep diffs focused to navigation labels/groups and the group icon integration point.
- Preserve existing permissions and resource registration behavior.
- Follow existing Filament and Capell admin patterns.
- Do not introduce package boundary violations.

### Testing

- Add or update focused tests around any changed navigation group labels where coverage already exists.
- Add a focused test for the Events navigation group icon behavior if the integration point is testable.
- Run the narrowest useful package-level Pest suites for changed packages during implementation.

### Out of Scope

- Redesigning the sidebar UI itself.
- Renaming individual resources beyond their group placement.
- Broader admin IA work outside the approved grouping.
