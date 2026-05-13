# Event Extension Points

Events registers an `event` page type, Filament resources, calendar feed routes, Blaze-optimized views, Livewire components, SEO schema hooks, and Publishing Studio workspace support when the package is installed.

## Runtime Surface

| Surface            | Code                                                                                                |
| ------------------ | --------------------------------------------------------------------------------------------------- |
| Install command    | `capell:events-install`                                                                             |
| Page type          | `CapellCore::registerPageType(name: event)`                                                         |
| Public feed routes | `events.ics`, `events/{listingPage}/feed.ics`                                                       |
| Render hooks       | `RegisterEventSchemaHooks`                                                                          |
| SEO schema         | `SchemaTemplateRegistry::registerIfMissing(SchemaTemplateTypeEnum::Event, new EventSchemaTemplate)` |
| Publishing Studio  | `WorkspaceRegistry::register(Event::class)`                                                         |
| Livewire           | `EventCalendar` via `LivewireComponentEnum`                                                         |

## Native Registration

Use `RegisterForEventOccurrenceAction` for Capell-owned RSVP flows. It locks the occurrence row, checks booking mode and status, handles waitlist placement, refreshes counts, and schedules notifications.

```php
use Capell\Events\Actions\RegisterForEventOccurrenceAction;
use Capell\Events\Data\EventRegistrationData;
use Capell\Events\Models\EventOccurrence;

/** @var EventOccurrence $occurrence */
$registration = RegisterForEventOccurrenceAction::run(
    $occurrence,
    new EventRegistrationData(
        name: 'Sam Editor',
        email: 'sam@example.test',
        quantity: 2,
        payload: [
            'source' => 'public_form',
        ],
    ),
);
```

Do not create `EventRegistration` rows directly from controllers or form handlers.

## External Booking Providers

`EventBookingProvider` is the read-side contract for external booking systems. Use it when Capell should display availability or a booking URL without owning the registration.

```php
use Capell\Events\Contracts\EventBookingProvider;
use Capell\Events\Models\EventOccurrence;

final class PartnerBookingProvider implements EventBookingProvider
{
    public function isAvailable(EventOccurrence $occurrence, int $quantity): bool
    {
        return $occurrence->status->isPubliclyBookable() && $quantity <= 4;
    }

    public function bookingUrl(EventOccurrence $occurrence): ?string
    {
        return 'https://events.example.test/book/' . $occurrence->getKey();
    }
}
```

Bind the provider in the package that owns the external booking integration.

## External Registration Providers

`EventRegistrationProvider` is for packages that need to create a registration through a non-native backend.

```php
use Capell\Events\Contracts\EventRegistrationProvider;
use Capell\Events\Data\EventRegistrationData;
use Capell\Events\Models\EventOccurrence;
use Capell\Events\Models\EventRegistration;

final class PartnerRegistrationProvider implements EventRegistrationProvider
{
    public function register(EventOccurrence $occurrence, EventRegistrationData $registrationData): EventRegistration
    {
        return EventRegistration::query()->create([
            'event_occurrence_id' => $occurrence->getKey(),
            'status' => 'pending',
            'name' => $registrationData->name,
            'email' => $registrationData->email,
            'phone' => $registrationData->phone,
            'quantity' => $registrationData->quantity,
            'payload' => $registrationData->payload,
            'registered_at' => now(),
        ]);
    }
}
```

Prefer the native action unless an external provider owns capacity, payment, or confirmation.

## Package Boundaries

- Events may integrate with SEO Suite and Publishing Studio through their public registries.
- Other packages should not reach into `EventModelRegistrar` or Filament resources.
- Keep public feed output free of admin or editor metadata.

## Verification

```bash
vendor/bin/pest packages/events/tests --configuration=phpunit.xml
```
