# Content Scheduler Page Design

## Context

The `publishing-studio` package already owns workspace approval, publishing, scheduled workspace publish dates, and a `ScheduledPublishingPage` that lists pages with future `visible_from` or `visible_until` values. The requested change is to make this surface a prominent editorial content scheduler, not a hidden workspace utility.

The implementation should expand the existing page instead of creating a new package. Scheduling logic remains in Actions, while Filament pages, tables, and widgets expose the admin experience.

## Goals

- Promote the existing scheduled publishing page as **Content Scheduler** in admin navigation.
- Show workspace-aware scheduled publishing alongside page publish and unpublish dates.
- Add editorial scheduling metadata for embargo dates and content review reminders.
- Provide calendar-friendly and table-friendly views of upcoming editorial events.
- Keep domain transitions in Actions and keep Filament classes thin.

## Non-Goals

- No new package.
- No bypass of workspace publishing or approval rules.
- No replacement of the existing `Publisher` pipeline.
- No notification delivery system beyond storing and surfacing review reminder dates.
- No external calendar integration.

## Architecture

The feature stays in `packages/publishing-studio`.

`ScheduledPublishingPage` will become the prominent Content Scheduler admin page. It may keep its class name and slug for compatibility, but its labels, title, badge, grouping, and subheading should describe the richer content scheduler.

Scheduling state is split by existing ownership:

- Workspace campaign publishing uses `publishing-studio.publish_at`.
- Workspace-level unpublish, embargo, and review reminders use new nullable workspace columns.
- Page-level publish and unpublish schedules continue to use `pages.visible_from` and `pages.visible_until`.

Actions own mutations:

- Schedule workspace publish date.
- Schedule workspace unpublish date.
- Set or clear workspace embargo date.
- Set or clear workspace review reminder date.
- Clear scheduler metadata safely.

Read models and reporting queries also live in Actions so widgets and tables do not contain business query logic.

## Data Model

Add nullable timestamp columns to `publishing-studio`:

- `embargo_until`: content should not be published before this time.
- `review_reminder_at`: editorial review reminder date.
- `unpublish_at`: workspace-level takedown date for campaign-led releases.

Indexes should support scheduler filtering:

- `embargo_until`
- `review_reminder_at`
- `unpublish_at`
- `status, publish_at`

The `Workspace` model should cast these timestamps to immutable datetimes and expose them in fillable properties. Existing `publish_at` behavior remains unchanged.

## Admin UX

The navigation item should read **Content Scheduler** and be more prominent than the current monitoring-style scheduled publishing link. It should use a calendar icon and badge upcoming scheduler items.

The page should expose:

- Overview widgets for upcoming publishes, upcoming unpublishes, embargoed items, and review reminders due.
- A table view for operational editing and filtering.
- A calendar-oriented view or grouped calendar list that can support a true calendar component later without reshaping the data.

The table should include both workspace and page schedule events through a scheduler event data layer, rather than trying to force different models into one Eloquent table directly.

Filters should support:

- Event type: publish, unpublish, embargo, review reminder.
- Source: workspace or page.
- Date range.
- Status.

Record actions should route to the appropriate underlying record:

- Workspace events open the workspace resource.
- Page events open the page editor/resource URL where available.

## Scheduler Events

Create a small Data object for scheduler rows with fields like:

- `id`
- `source_type`
- `source_id`
- `title`
- `event_type`
- `scheduled_for`
- `status`
- `description`
- `record_url`

Build Actions should produce these events from publishing-studio and pages. This avoids mixing unrelated Eloquent models in a single table and keeps calendar rendering stable.

## Embargo Behavior

Embargo dates are a guardrail on workspace publishing. A workspace with `embargo_until` in the future should not publish, even when `publish_at` is due.

The publish pipeline should fail clearly before making live changes. Scheduled jobs should leave embargoed work in the scheduled state so it can publish on a later tick once the embargo passes.

## Review Reminder Behavior

Review reminders are editorial signals, not state transitions. A reminder due date should appear in the scheduler and dashboard widgets. Clearing or changing a reminder should be explicit through an Action.

## Calendar View

The first implementation can use a grouped calendar list by date if a full calendar component would add dependency or layout risk. The data shape should be calendar-ready so a richer month/week view can be added later without changing domain logic.

## Testing

Tests should cover:

- Workspace schedule metadata Actions.
- Embargo blocking a publish before `embargo_until`.
- Scheduled job leaving embargoed workspace scheduled until allowed.
- Scheduler query Actions returning page publish, page unpublish, workspace publish, workspace unpublish, embargo, and review reminder events.
- Filament page label, group, badge, and table/calendar visibility.
- Widget data for upcoming publishes, unpublishes, embargoes, and due reminders.

## Rollout

The change should be incremental:

1. Add database columns, casts, and Action coverage.
2. Expand scheduler query/data Actions.
3. Update `ScheduledPublishingPage` labels and navigation prominence.
4. Replace the page table with scheduler event data and filters.
5. Add widgets and tests.
