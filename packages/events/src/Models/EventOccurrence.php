<?php

declare(strict_types=1);

namespace Capell\Events\Models;

use Capell\Core\Models\PageUrl;
use Capell\Events\Database\Factories\EventOccurrenceFactory;
use Capell\Events\Enums\EventBookingModeEnum;
use Capell\Events\Enums\EventLocationModeEnum;
use Capell\Events\Enums\EventOccurrenceStatusEnum;
use Capell\Events\Enums\EventRegistrationStatusEnum;
use Capell\Events\Enums\EventVisibilityEnum;
use Capell\Events\Models\Event as EventModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property bool $all_day
 * @property EventBookingModeEnum $booking_mode
 * @property int|null $capacity
 * @property CarbonImmutable|null $ends_at
 * @property string $occurrence_key
 * @property int $registration_count
 * @property CarbonImmutable $starts_at
 * @property EventOccurrenceStatusEnum $status
 * @property string $timezone
 * @property bool $waitlist_enabled
 * @property-read EventModel $event
 * @property-read EventVenue|null $venue
 */
class EventOccurrence extends Model
{
    /** @use HasFactory<EventOccurrenceFactory> */
    use HasFactory;

    protected $table = 'event_occurrences';

    protected $guarded = [];

    protected static string $factory = EventOccurrenceFactory::class;

    public function event(): BelongsTo
    {
        return $this->belongsTo(EventModel::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(EventVenue::class, 'event_venue_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function confirmedRegistrationQuantity(): int
    {
        return (int) $this->registrations()
            ->whereIn('status', [
                EventRegistrationStatusEnum::Pending,
                EventRegistrationStatusEnum::Confirmed,
            ])
            ->sum('quantity');
    }

    public function remainingCapacity(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->confirmedRegistrationQuantity());
    }

    public function isFullForQuantity(int $quantity): bool
    {
        $remainingCapacity = $this->remainingCapacity();

        return $remainingCapacity !== null && $quantity > $remainingCapacity;
    }

    public function occurrenceUrl(): ?string
    {
        if (! $this->isPubliclyVisible()) {
            return null;
        }

        $pageUrlModel = $this->event?->getRelationValue('pageUrl');

        if (! $pageUrlModel instanceof PageUrl || ! $pageUrlModel->exists) {
            return null;
        }

        $pageUrl = $pageUrlModel->full_url;

        if ($pageUrl === null || $pageUrl === '') {
            return null;
        }

        return rtrim($pageUrl, '/') . '/' . $this->starts_at->setTimezone($this->timezone)->toDateString();
    }

    public function isPubliclyVisible(): bool
    {
        $event = $this->event;

        return $this->visibility === EventVisibilityEnum::Public
            && $this->status !== EventOccurrenceStatusEnum::Cancelled
            && $event instanceof EventModel
            && $event->visibility === EventVisibilityEnum::Public
            && ! $event->isPending()
            && ! $event->isExpired();
    }

    protected function scopeOrdered(Builder $query): Builder
    {
        return $query->oldest('starts_at')->orderBy('id');
    }

    protected function scopeInRange(Builder $query, CarbonImmutable $startsAt, CarbonImmutable $endsAt): Builder
    {
        return $query
            ->where('starts_at', '<=', $endsAt)
            ->where(function (Builder $query) use ($startsAt): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $startsAt);
            });
    }

    protected function scopeUpcoming(Builder $query, ?CarbonImmutable $from = null): Builder
    {
        $from ??= CarbonImmutable::now();

        return $query->where('starts_at', '>=', $from);
    }

    protected function scopePublic(Builder $query): Builder
    {
        return $query
            ->where('visibility', EventVisibilityEnum::Public->value)
            ->where('status', '!=', EventOccurrenceStatusEnum::Cancelled->value);
    }

    protected function casts(): array
    {
        return [
            'all_day' => 'boolean',
            'booking_mode' => EventBookingModeEnum::class,
            'capacity' => 'integer',
            'ends_at' => 'immutable_datetime',
            'is_override' => 'boolean',
            'location_mode' => EventLocationModeEnum::class,
            'meta' => 'json',
            'override_data' => 'json',
            'registration_count' => 'integer',
            'starts_at' => 'immutable_datetime',
            'status' => EventOccurrenceStatusEnum::class,
            'visibility' => EventVisibilityEnum::class,
            'waitlist_enabled' => 'boolean',
        ];
    }
}
