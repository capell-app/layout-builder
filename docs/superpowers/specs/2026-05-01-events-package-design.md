# Events Package Design

## Goal

Create a `capell-app/events` package for publishing event content in Capell. Events are custom page types similar to Blog articles: each event is a pageable, translatable, publishable content item with its own frontend detail page, admin resource, layout, URL, media, and metadata.

The package also adds event-specific browsing surfaces: upcoming listings, an interactive Livewire calendar with month navigation and selected-date listings, persisted recurring event occurrences, booking links, calendar feeds, and JSON-LD event schema output.

## Package Shape

The package follows the Blog package structure and Capell package boundaries.

- Namespace: `Capell\Events`
- Composer package: `capell-app/events`
- Providers:
    - `EventsServiceProvider` for shared model, page type, Livewire, Blade, package metadata, assets, and morph-map registration.
    - `AdminServiceProvider` for Filament resource registration, configurators, default page creation, and navigation integration.
    - `FrontendServiceProvider` for frontend routes, sitemap integration, and feed exposure.
    - `ConsoleServiceProvider` for install/setup/demo commands.
- Dependencies:
    - core
    - admin
    - frontend
    - layout-builder
    - navigation
    - publishing-studio

Tags are not included in the first implementation. The package can add tags later using the same bridge pattern Blog uses, but events do not need tags to satisfy listings, calendar, feeds, or schema.

## Domain Model

### Event

`Capell\Events\Models\Event` implements:

- `Pageable`
- `PageCacheable`
- `Publishable`
- `Translatable`
- `Typeable`
- `Userstampable`
- `HasMedia`

It uses the same core traits as Blog articles where applicable:

- `BelongsToWorkspace`
- `Cloneable`
- `CloneableExcept`
- `HasAssets`
- `HasCapellMedia`
- `HasFactory`
- `HasMetaData`
- `HasMorphModelRelations`
- `HasPageOrdering`
- `HasPublishDates`
- `HasTranslations`
- `HasType`
- `HasTypes`
- `HasUserstamps`
- `LogsActivity`
- `SoftDeletes`

The model stores general content and authoring data in `events`:

- `id`
- `uuid`
- `workspace_id`
- `shadowed_by_workspace_id`
- `name`
- `type_id`
- `layout_id`
- `site_id`
- `meta`
- `visible_from`
- `visible_until`
- `order`
- userstamps
- timestamps
- soft deletes

Event-specific structured values live in `meta` through typed Data classes rather than bare arrays:

- `EventScheduleData`
- `EventLocationData`
- `EventBookingData`
- `EventSchemaData`

### EventOccurrence

`Capell\Events\Models\EventOccurrence` stores concrete dates generated from an event schedule.

Columns:

- `id`
- `event_id`
- `site_id`
- `starts_at`
- `ends_at`
- `timezone`
- `status`
- `location`
- `booking`
- `schema`
- `is_cancelled`
- timestamps

The occurrence row is intentionally denormalized enough for fast listing, filtering, feeds, and schema output. It keeps a snapshot of location and booking values so future event edits can regenerate upcoming occurrences without surprising already-exported data.

## Structured Data

The package uses Spatie Laravel Data classes at every boundary.

- `EventScheduleData`
    - `startsAt`
    - `endsAt`
    - `timezone`
    - `recurrence`
    - `generateUntil`
- `EventRecurrenceData`
    - `frequency`
    - `interval`
    - `weekdays`
    - `monthDay`
    - `until`
    - `count`
- `EventLocationData`
    - `type`
    - `name`
    - `address`
    - `url`
    - `latitude`
    - `longitude`
- `EventBookingData`
    - `url`
    - `label`
    - `opensAt`
    - `closesAt`
- `EventCalendarDayData`
    - `date`
    - `isCurrentMonth`
    - `isToday`
    - `isSelected`
    - `occurrenceCount`
- `EventSchemaData`
    - values needed for schema.org `Event` JSON-LD output.

Model JSON columns use Data casts such as `AsData` where the repo dependencies support it.

## Enums

The first implementation includes backed enums:

- `EventPageTypeEnum`
    - `Event`
    - `Events`
    - `Calendar`
- `EventRecurrenceFrequencyEnum`
    - `None`
    - `Daily`
    - `Weekly`
    - `Monthly`
- `EventOccurrenceStatusEnum`
    - `Scheduled`
    - `Cancelled`
    - `Postponed`
- `EventLocationTypeEnum`
    - `Physical`
    - `Online`
    - `Hybrid`
- `LivewirePageComponentEnum`
    - events index
    - event calendar

Filament-facing enums implement `HasLabel` when used as Select or ToggleButtons options.

## Recurrence Design

Recurring events use authored rules plus persisted occurrences.

The event stores its recurrence rule in `EventScheduleData`. `GenerateEventOccurrencesAction` expands that rule into `event_occurrences` rows within a bounded generation window. The default generation window is 18 months from the event start unless `generateUntil`, `until`, or `count` ends the series earlier.

This is the recommended approach because it keeps frontend reads straightforward:

- upcoming listings query rows by `starts_at`
- selected-date listings query rows by date range
- month navigation queries rows within calendar bounds
- iCal feeds stream rows
- JSON-LD schema can be generated per occurrence

Regeneration is handled by explicit Actions:

- `GenerateEventOccurrencesAction`
- `SyncEventOccurrencesAction`
- `DeleteFutureEventOccurrencesAction`
- `BuildEventCalendarMonthAction`
- `BuildEventSchemaAction`

The first version supports daily, weekly, and monthly recurrence. It does not implement exception dates, split-series editing, or complex RRULE import. Those can be added later without changing the occurrence storage model.

## Admin Experience

The package registers an Event resource as a Capell page resource variation, following Blog article patterns.

Admin form sections:

- Content translations and page fields inherited from the default page configurator.
- Event schedule:
    - start date/time
    - end date/time
    - timezone
    - recurrence frequency
    - interval
    - weekly weekdays for weekly recurrence
    - month day for monthly recurrence
    - until/count/generation window
- Location:
    - physical, online, or hybrid
    - venue/name
    - address
    - online URL
    - coordinates
- Booking:
    - booking URL
    - booking label
    - optional open/close dates
- SEO/schema:
    - schema status
    - attendance mode inferred from location type
    - organizer/performer text fields for JSON-LD when required.

The resource table adds event-specific columns:

- event title/name
- next occurrence start date
- location summary
- recurrence summary
- booking link indicator
- site
- languages
- publish state

Writes go through Actions. Resource pages and configurators only collect form state and call Actions.

## Frontend Experience

### Event Detail Pages

Each event has its own pageable detail page. It uses the same URL, translation, layout, media, and cache behavior as Blog articles.

The detail view exposes:

- event title/content
- next occurrence date and time
- timezone-aware formatted date/time
- location name/address/link
- booking action if available
- upcoming occurrences for recurring events
- JSON-LD `Event` schema

### Events Index

The default events page lists upcoming occurrences ordered by `starts_at`.

The listing groups by date where the frontend layout supports grouping. If the shared results layout cannot support date grouping without broad changes, the first version renders a package-owned Livewire view.

### Interactive Calendar

The calendar is a Livewire component registered as a page component.

State:

- current year/month
- selected date
- site
- language

Interactions:

- previous month
- next month
- today
- select date

The month grid queries occurrences from the visible calendar window, including leading and trailing days needed to fill complete weeks. Selecting a date updates the selected-date occurrence list without a full page reload.

### Calendar Feeds

The package exposes an iCalendar feed route for published upcoming occurrences.

Feed behavior:

- site-aware
- language-aware when the route includes language context
- emits `VEVENT` entries with stable UIDs
- includes title, description, URL, location, start, end, timezone, status, and booking URL
- limits output to a bounded future window for performance

The first implementation can generate iCal text directly in a focused Action without adding a Composer dependency.

## Schema Output

`BuildEventSchemaAction` creates schema.org JSON-LD for event detail pages and occurrence-backed listings where needed.

Schema fields:

- `@context`
- `@type`
- `name`
- `description`
- `startDate`
- `endDate`
- `eventStatus`
- `eventAttendanceMode`
- `location`
- `image`
- `url`
- `offers` when a booking URL exists
- `organizer` when configured

The Blade output uses a small component or render hook so schema remains package-owned and does not require core/frontend changes.

## Default Setup

The setup command creates:

- Event page type
- Events index page type
- Calendar page type
- Event layout
- Events index layout
- Calendar layout
- default Events page
- optional Calendar page under Events

Blog requires LayoutBuilder first. Events also requires LayoutBuilder first because it uses layouts/widgets and follows the same page model.

## Testing

Primary tests:

- package manifest requirements
- model implements expected contracts
- model morph map/page variation registration
- event occurrence generation for one-off, daily, weekly, and monthly schedules
- occurrence regeneration removes future stale rows while preserving past rows
- upcoming listing orders occurrences by start date
- calendar month builder returns complete weeks and correct occurrence counts
- selected date listing returns only occurrences in that date range
- iCal feed emits valid core fields
- JSON-LD schema contains expected Event fields
- Filament resource registration is gated by package installation

Commands:

- `vendor/bin/pest packages/events/tests`
- `composer preflight` before commit once implementation exists

## Deferred Scope

Not included in the first implementation:

- recurrence exception dates
- splitting a recurring series from a selected occurrence
- external calendar import
- paid booking flows
- waitlists or capacity management
- tag integration
- map rendering

These are intentionally deferred to keep the first package useful and maintainable.
