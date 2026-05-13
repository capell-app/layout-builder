# Events

Events adds event records, venues, occurrences, registrations, calendar pages, notifications, and iCalendar feed support to Capell.

## At A Glance

- Package: `capell-app/events`
- Namespace: `Capell\Events\`
- Surfaces: Filament admin, Livewire, console, HTTP, database
- Service providers: `packages/events/src/Providers/EventsServiceProvider.php`
- Capell dependencies: `capell-app/admin`, `capell-app/frontend`, `capell-app/navigation`, `capell-app/publishing-studio`
- Third-party dependencies: `rlanvin/php-rrule`, `spatie/icalendar-generator`

## What It Adds

- Events adds event records, venues, occurrences, registrations, calendar pages, notifications, and iCalendar feed support to Capell.
- Admin resources: `EventOccurrenceResource`, `EventRegistrationResource`, `EventResource`, `EventVenueResource`.
- Livewire components: `EventCalendar`, `EventsCalendarPage`, `EventsListingPage`.
- Package setup or maintenance commands.

## Technical Shape

- EventsServiceProvider registers admin resources, frontend pages, calendar feed routes, migrations, and translations.
- Recurrence handling uses `rlanvin/php-rrule`; calendar feed output uses `spatie/icalendar-generator`.
- Event registration and booking integrations are behind contracts so the package can accept different registration providers.

## Code Map

| Area      | Path                            | Purpose                                                             |
| --------- | ------------------------------- | ------------------------------------------------------------------- |
| Actions   | `packages/events/src/Actions`   | Domain operations. Test these directly where possible.              |
| Data      | `packages/events/src/Data`      | Structured payloads, form state, view models, and integration data. |
| Enums     | `packages/events/src/Enums`     | Persisted states and Filament option values.                        |
| Models    | `packages/events/src/Models`    | Eloquent records owned by the package.                              |
| Filament  | `packages/events/src/Filament`  | Admin resources, pages, widgets, and settings UI.                   |
| Livewire  | `packages/events/src/Livewire`  | Interactive frontend or admin components.                           |
| HTTP      | `packages/events/src/Http`      | Controllers, middleware, and request handling.                      |
| Providers | `packages/events/src/Providers` | Registration, extension hooks, routes, migrations, and resources.   |
| Resources | `packages/events/resources`     | Views, translations, assets, and package resources.                 |
| Routes    | `packages/events/routes`        | Route files loaded by the service provider.                         |
| Database  | `packages/events/database`      | Migrations, seeders, and settings migrations.                       |
| Tests     | `packages/events/tests`         | Package-level Pest coverage.                                        |

## Admin Surface

- Resources: `EventOccurrenceResource`, `EventRegistrationResource`, `EventResource`, `EventVenueResource`.
- Pages: `CreateEvent`, `EditEvent`, `EventCalendarPage`, `ListEvents`, `ManageEventOccurrences`, `ManageEventRegistrations`, `ManageEventVenues`.
- Widgets: `EventCalendarWidget`.

## Runtime Surface

- Livewire: `EventCalendar`, `EventsCalendarPage`, `EventsListingPage`.
- Controllers: `CalendarFeedController`.
- Routes: `packages/events/routes/web.php`.

## Commands

- `capell:events-install` (packages/events/src/Console/Commands/InstallCommand.php)

## Data And Persistence

- Models: `Event`, `EventNotificationLog`, `EventOccurrence`, `EventRegistration`, `EventVenue`.
- Migrations: `2026_05_10_190848_01_create_event_venues_table.php`, `2026_05_10_190848_02_create_events_table.php`, `2026_05_10_190848_03_create_event_occurrences_table.php`, `2026_05_10_190848_04_create_event_registrations_table.php`, `2026_05_10_190848_05_create_event_notification_logs_table.php`.
- Data objects live in `src/Data/`; use them for payloads, form state, and view models.

## Extension Points

- Contracts: `EventBookingProvider`, `EventRegistrationProvider`.
- Register Capell extension points, routes, migrations, settings, render hooks, and resources from service providers.

## Install Impact

- Adds event, venue, occurrence, registration, and notification log tables.
- Adds event management resources to the admin panel.
- Adds frontend calendar/listing components and a calendar feed route.

## Install And Setup

- Install with `composer require capell-app/events` in the host Capell application.
- Run migrations through the host application package install flow.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.

## Docs

- [event-extension-points.md](docs/event-extension-points.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/events/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Treat public routes as untrusted input and keep validation, permission checks, and side effects inside actions or dedicated services.
- Put behaviour changes in `src/Actions/`; UI classes, commands, and controllers should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Use backed enums for persisted values and enum labels for Filament options.
