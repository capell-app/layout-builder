<?php

declare(strict_types=1);

namespace Capell\Address\Models;

use Capell\Address\Database\Factories\CountryFactory;
use Capell\Address\Observers\CountryObserver;
use Capell\Core\Models\Concerns\HasDefault;
use Capell\Core\Models\Concerns\HasStatus;
use Capell\Core\Models\Contracts\Defaultable;
use Capell\Core\Models\Language;
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
use Wildside\Userstamps\Userstamps;

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
 */
#[ObservedBy(CountryObserver::class)]
class Country extends Model implements Defaultable
{
    use HasDefault;

    /** @use HasFactory<CountryFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasStatus;
    use SoftDeletes;
    use Userstamps;

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
