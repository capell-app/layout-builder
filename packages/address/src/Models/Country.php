<?php

declare(strict_types=1);

namespace Capell\Address\Models;

use Capell\Address\Database\Factories\CountryFactory;
use Capell\Address\Observers\CountryObserver;
use Capell\Core\Models\Concerns\HasDefault;
use Capell\Core\Models\Concerns\HasStatus;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Defaultable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Language;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

/**
 * @mixin Model
 *
 * @property-read User|null $creator
 * @property-read User|null $destroyer
 * @property-read User|null $editor
 * @property-read Language|null $language
 * @property-read Collection|Language[] $languages
 * @property-read int|null $languages_count
 *
 * @method static Builder<static>|Country default(bool $default = true)
 * @method static Builder<static>|Country disabled()
 * @method static Builder<static>|Country enabled()
 * @method static CountryFactory factory($count = null, $state = [])
 * @method static Builder<static>|Country newModelQuery()
 * @method static Builder<static>|Country newQuery()
 * @method static Builder<static>|Country nonDefault()
 * @method static Builder<static>|Country onlyTrashed()
 * @method static Builder<static>|Country ordered()
 * @method static Builder<static>|Country query()
 * @method static Builder<static>|Country status(bool $enabled)
 * @method static Builder<static>|Country withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Country withoutTrashed()
 *
 * @mixin Model
 *
 * @property int $id
 * @property string $name
 * @property string|null $iso2
 * @property string|null $iso3
 * @property int|null $language_id
 * @property int $default
 * @property int $status
 * @property array<array-key, mixed>|null $meta
 * @property CarbonImmutable|null $deleted_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 *
 * @method static Builder<static>|Country whereCreatedAt($value)
 * @method static Builder<static>|Country whereCreatedBy($value)
 * @method static Builder<static>|Country whereDefault($value)
 * @method static Builder<static>|Country whereDeletedAt($value)
 * @method static Builder<static>|Country whereDeletedBy($value)
 * @method static Builder<static>|Country whereId($value)
 * @method static Builder<static>|Country whereIso2($value)
 * @method static Builder<static>|Country whereIso3($value)
 * @method static Builder<static>|Country whereLanguageId($value)
 * @method static Builder<static>|Country whereMeta($value)
 * @method static Builder<static>|Country whereName($value)
 * @method static Builder<static>|Country whereStatus($value)
 * @method static Builder<static>|Country whereUpdatedAt($value)
 * @method static Builder<static>|Country whereUpdatedBy($value)
 *
 * @mixin Model
 */
#[ObservedBy(CountryObserver::class)]
class Country extends Model implements Defaultable, Userstampable
{
    use HasDefault;

    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasStatus;
    use HasUserstamps;
    use SoftDeletes;

    protected $fillable = [
        'default',
        'iso2',
        'iso3',
        'language_id',
        'meta',
        'name',
        'status',
    ];

    protected static string $factory = CountryFactory::class;

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function languages(): BelongsToJson
    {
        return $this->belongsToJson(Language::class, 'meta->languages');
    }

    protected function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    protected function casts(): array
    {
        return [
            'meta' => 'json',
        ];
    }
}
