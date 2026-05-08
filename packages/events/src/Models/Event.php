<?php

declare(strict_types=1);

namespace Capell\Events\Models;

use Bkwld\Cloner\Cloneable;
use Capell\Core\Concerns\HasCapellMedia;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Concerns\CloneableExcept;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasMorphModelRelations;
use Capell\Core\Models\Concerns\HasPageOrdering;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasTranslations;
use Capell\Core\Models\Concerns\HasType;
use Capell\Core\Models\Concerns\HasTypes;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Contracts\Translatable;
use Capell\Core\Models\Contracts\Typeable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Translation;
use Capell\Core\Models\Type;
use Capell\Events\Database\Factories\EventFactory;
use Capell\Events\Enums\EventBookingModeEnum;
use Capell\Events\Enums\EventLocationModeEnum;
use Capell\Events\Enums\EventVisibilityEnum;
use Capell\PublishingStudio\BelongsToWorkspace;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

/**
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $visible_from
 * @property CarbonImmutable|null $visible_until
 * @property EventVisibilityEnum $visibility
 * @property int|null $site_id
 * @property string $name
 * @property string $timezone
 * @property-read PageUrl|null $pageUrl
 * @property-read Site $site
 * @property-read Translation|null $translation
 * @property-read Type $type
 */
class Event extends Model implements HasMedia, Pageable, PageCacheable, Publishable, Translatable, Typeable, Userstampable
{
    use BelongsToWorkspace;
    use Cloneable;
    use CloneableExcept;
    use HasAssets;
    use HasCapellMedia;

    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasMetaData;
    use HasMorphModelRelations;
    use HasPageOrdering;
    use HasPublishDates;
    use HasTranslations;
    use HasType;
    use HasTypes;
    use HasUserstamps;
    use SoftDeletes;

    protected $table = 'events';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'all_day',
        'booking_label',
        'booking_mode',
        'booking_url',
        'capacity',
        'ends_at',
        'event_venue_id',
        'layout_id',
        'location_mode',
        'meta',
        'name',
        'notification_settings',
        'order',
        'recurrence',
        'recurrence_rule',
        'site_id',
        'starts_at',
        'timezone',
        'type_id',
        'uuid',
        'visible_from',
        'visible_until',
        'visibility',
        'waitlist_enabled',
    ];

    protected array $clone_exempt_attributes = [
        'hidden',
    ];

    protected static string $factory = EventFactory::class;

    public static function getDefaultType(?string $group): ?Type
    {
        return Type::query()
            ->pageType()
            ->when($group !== null, fn (Builder $query): Builder => $query->adminResource($group))
            ->where('key', 'event')
            ->ordered()
            ->first();
    }

    public static function hasPageHierarchy(): bool
    {
        return false;
    }

    public static function defaultOrdering(): PageOrderEnum
    {
        return PageOrderEnum::Latest;
    }

    public function shouldLogVisit(): bool
    {
        return true;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Image->value)->singleFile();
    }

    public function getParentUrl(Language $language, bool $fullUrl = false): string
    {
        $url = $fullUrl ? $this->site->getSiteDomainUrl($language) : '/';

        return $url . 'events';
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(EventVenue::class, 'event_venue_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(EventOccurrence::class);
    }

    /** @return MorphOne<PageUrl, self> */
    public function pageUrl(): MorphOne
    {
        return $this->morphOne(PageUrl::class, 'pageable')->withDefault(['site_id' => $this->site_id]);
    }

    /** @return MorphMany<PageUrl, self> */
    public function pageUrls(): MorphMany
    {
        $relation = $this->morphMany(PageUrl::class, 'pageable');

        if (method_exists($relation, 'chaperone')) {
            $relation->chaperone('pageable');
        }

        return $relation;
    }

    /** @return MorphMany<self, self> */
    public function canonicalPages(): MorphMany
    {
        return $this->morphMany(
            self::class,
            'canonical_pageable',
            'meta->canonical_pageable_type',
            'meta->canonical_pageable_id',
        );
    }

    public function canonicalPage(): MorphTo
    {
        return $this->morphTo(type: 'meta->canonical_pageable_type', id: 'meta->canonical_pageable_id');
    }

    public function draftRevisions(): HasMany
    {
        return $this->hasMany(self::class, 'id', 'id')->whereRaw('0=1');
    }

    public function getPublishDate(): ?CarbonImmutable
    {
        $date = $this->visible_from ?? $this->starts_at ?? $this->created_at;

        return $date !== null ? CarbonImmutable::make($date) : null;
    }

    /** @return array<string, mixed>|null */
    protected function getUrlParamsAttribute(): ?array
    {
        return $this->type->meta['url_params'] ?? null;
    }

    protected function casts(): array
    {
        return [
            'all_day' => 'boolean',
            'booking_mode' => EventBookingModeEnum::class,
            'capacity' => 'integer',
            'ends_at' => 'immutable_datetime',
            'location_mode' => EventLocationModeEnum::class,
            'meta' => 'json',
            'notification_settings' => 'json',
            'recurrence' => 'json',
            'starts_at' => 'immutable_datetime',
            'visible_from' => 'immutable_datetime',
            'visible_until' => 'immutable_datetime',
            'visibility' => EventVisibilityEnum::class,
            'waitlist_enabled' => 'boolean',
        ];
    }
}
