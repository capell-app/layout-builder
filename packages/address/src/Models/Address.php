<?php

declare(strict_types=1);

namespace Capell\Address\Models;

use Capell\Address\Database\Factories\AddressFactory;
use Capell\Address\Observers\AddressObserver;
use Capell\Core\Models\Concerns\HasDefault;
use Capell\Core\Models\Concerns\HasStatus;
use Capell\Core\Models\Contracts\Defaultable;
use Capell\Core\Models\Site;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Wildside\Userstamps\Userstamps;

/**
 * @mixin Model
 *
 * @property string|null $city
 * @property int|null $country_id
 * @property bool|null $default
 * @property string|null $line1
 * @property string|null $line2
 * @property array|null $meta
 * @property string|null $name
 * @property string|null $postal_code
 * @property string|null $state
 * @property bool|null $status
 * @property-read Country|null $country
 * @property-read User|null $creator
 * @property-read User|null $destroyer
 * @property-read User|null $editor
 * @property-read string $full_address
 * @property-read Collection<int, Site> $sites
 * @property-read int|null $sites_count
 *
 * @method static Builder<static>|Address default(bool $default = true)
 * @method static Builder<static>|Address disabled()
 * @method static Builder<static>|Address enabled()
 * @method static AddressFactory factory($count = null, $state = [])
 * @method static Builder<static>|Address newModelQuery()
 * @method static Builder<static>|Address newQuery()
 * @method static Builder<static>|Address nonDefault()
 * @method static Builder<static>|Address onlyTrashed()
 * @method static Builder<static>|Address ordered()
 * @method static Builder<static>|Address query()
 * @method static Builder<static>|Address status(bool $enabled)
 * @method static Builder<static>|Address withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Address withoutTrashed()
 *
 * @mixin Model
 */
#[ObservedBy(AddressObserver::class)]
class Address extends Model implements Defaultable
{
    use HasDefault;

    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasStatus;
    use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'city',
        'country_id',
        'default',
        'line1',
        'line2',
        'meta',
        'name',
        'postal_code',
        'state',
        'status',
    ];

    protected static string $factory = AddressFactory::class;

    /**
     * Get the country for the address.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'meta->address_id');
    }

    protected function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderBy('line1')
            ->orderBy('city')
            ->orderBy('state')
            ->orderBy('postal_code')
            ->orderBy('country_id');
    }

    protected function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->line1,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country?->name,
        ]);

        return implode(', ', $parts);
    }

    protected function casts(): array
    {
        return [
            'meta' => 'json',
            'default' => 'boolean',
            'status' => 'boolean',
        ];
    }
}
