# Content Scheduler Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Expand the existing workspaces scheduled publishing page into a prominent Content Scheduler with workspace publish/unpublish dates, embargoes, review reminders, and calendar-ready scheduler rows.

**Architecture:** Keep the feature in `packages/workspaces`. Mutations live in focused Actions, read models live in Data and report Actions, and Filament pages/widgets render the resulting scheduler state.

**Tech Stack:** PHP 8.2, Laravel, Filament, Pest, Spatie Laravel Data, Lorisleiva Actions.

---

### Task 1: Scheduler Metadata And Embargo Guard

**Files:**

- Create: `packages/workspaces/database/migrations/z_add_content_scheduler_columns_to_workspaces_table.php`
- Modify: `packages/workspaces/src/Models/Workspace.php`
- Create: `packages/workspaces/src/Actions/SetWorkspaceSchedulerMetadataAction.php`
- Create: `packages/workspaces/src/Exceptions/EmbargoActiveException.php`
- Modify: `packages/workspaces/src/Publisher.php`
- Modify: `packages/workspaces/src/PublishScheduledWorkspacesJob.php`
- Test: `packages/workspaces/tests/Integration/WorkspaceSchedulerMetadataActionTest.php`
- Test: `packages/workspaces/tests/Integration/PublisherEmbargoTest.php`

- [x] Write failing action and embargo tests.
- [x] Add scheduler metadata columns and immutable casts.
- [x] Add metadata Action and embargo exception.
- [x] Block publish before `embargo_until`.
- [x] Leave scheduled workspaces queued while embargoed.

### Task 2: Calendar-Ready Scheduler Event Read Model

**Files:**

- Create: `packages/workspaces/src/Enums/SchedulerEventTypeEnum.php`
- Create: `packages/workspaces/src/Data/SchedulerEventData.php`
- Create: `packages/workspaces/src/Actions/Reports/BuildContentSchedulerEventsAction.php`
- Test: `packages/workspaces/tests/Admin/Feature/Actions/Reports/BuildContentSchedulerEventsActionTest.php`

- [x] Normalize page and workspace schedule rows into `SchedulerEventData`.
- [x] Support event type and source filtering.
- [x] Sort rows by scheduled time for table and calendar views.

### Task 3: Filament Page, Table, And Workspace UX

**Files:**

- Modify: `packages/workspaces/src/Filament/Pages/ScheduledPublishingPage.php`
- Modify: `packages/workspaces/src/Filament/Pages/Tables/ScheduledPublishingTable.php`
- Create: `packages/workspaces/src/Filament/Resources/Workspaces/Actions/SchedulerMetadataAction.php`
- Modify: `packages/workspaces/src/Filament/Resources/Workspaces/Tables/WorkspacesTable.php`
- Create: `packages/workspaces/resources/lang/en/scheduler.php`
- Test: `packages/workspaces/tests/Admin/Feature/Filament/Pages/ScheduledPublishingPageTest.php`

- [x] Keep the old slug but relabel the page as Content Scheduler.
- [x] Move the page into prominent Content navigation.
- [x] Replace the page-only table with mixed scheduler event records.
- [x] Add resource action for unpublish, embargo, and review reminder dates.

### Task 4: Dashboard And Calendar Widgets

**Files:**

- Create: `packages/workspaces/src/Filament/Widgets/ContentSchedulerOverviewWidget.php`
- Create: `packages/workspaces/src/Filament/Widgets/ContentSchedulerCalendarWidget.php`
- Create: `packages/workspaces/resources/views/widgets/content-scheduler-calendar.blade.php`
- Modify: `packages/workspaces/src/Providers/AdminServiceProvider.php`
- Modify: `packages/workspaces/src/Filament/Settings/Contributors/DefaultDashboardSettingsContributor.php`
- Test: `packages/workspaces/tests/Feature/Filament/Widgets/ContentSchedulerOverviewWidgetTest.php`

- [x] Add overview stats for publish, unpublish, embargo, and review reminders.
- [x] Add a grouped calendar view widget without introducing a new calendar dependency.
- [x] Register the widget and translations.

### Task 5: Verification

- [x] Run focused scheduler tests.
- [x] Run Pint on touched files.
- [ ] Run full workspaces suite to green. Current suite has unrelated package-bootstrap failures outside this change.
