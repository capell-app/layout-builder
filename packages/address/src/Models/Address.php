<?php

declare(strict_types=1);

namespace Capell\Address\Models;

use Capell\Address\Database\Factories\AddressFactory;
use Capell\Address\Observers\AddressObserver;
use Capell\Core\Models\Concerns\HasDefault;
use Capell\Core\Models\Concerns\HasStatus;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Defaultable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Site;
use Carbon\CarbonImmutable;
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
 *
 * @property int $id
 * @property CarbonImmutable|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static Builder<static>|Address whereCity($value)
 * @method static Builder<static>|Address whereCountryId($value)
 * @method static Builder<static>|Address whereCreatedAt($value)
 * @method static Builder<static>|Address whereCreatedBy($value)
 * @method static Builder<static>|Address whereDefault($value)
 * @method static Builder<static>|Address whereDeletedAt($value)
 * @method static Builder<static>|Address whereDeletedBy($value)
 * @method static Builder<static>|Address whereId($value)
 * @method static Builder<static>|Address whereLine1($value)
 * @method static Builder<static>|Address whereLine2($value)
 * @method static Builder<static>|Address whereMeta($value)
 * @method static Builder<static>|Address whereName($value)
 * @method static Builder<static>|Address wherePostalCode($value)
 * @method static Builder<static>|Address whereState($value)
 * @method static Builder<static>|Address whereStatus($value)
 * @method static Builder<static>|Address whereUpdatedAt($value)
 * @method static Builder<static>|Address whereUpdatedBy($value)
 *
 * @mixin Model
 */
#[ObservedBy(AddressObserver::class)]
class Address extends Model implements Defaultable, Userstampable
{
    use HasDefault;

    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasStatus;
    use HasUserstamps;
    use SoftDeletes;

    protected $guarded = [];

    /*protected $fillable = [
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
    ];*/

    protected static string $factory = AddressFactory::class;

    public static function findAddress(string $line1, string $postalCode, int $countryId): ?self
    {
        return self::query()
            ->where('line1', $line1)
            ->where('postal_code', $postalCode)
            ->where('country_id', $countryId)
            ->first();
    }

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
            ->orderBy('line2')
            ->orderBy('city')
            ->orderBy('state')
            ->orderBy('postal_code')
            ->orderBy('country_id');
    }

    protected function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->line1,
            $this->line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country?->name,
        ], fn (?string $part): bool => $part !== null);

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
