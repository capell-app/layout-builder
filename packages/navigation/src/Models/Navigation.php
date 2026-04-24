<?php

declare(strict_types=1);

namespace Capell\Navigation\Models;

use Bkwld\Cloner\Cloneable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasType;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Contracts\Typeable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Database\Factories\NavigationFactory;
use Capell\Navigation\Observers\NavigationObserver;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Spatie\LaravelData\DataCollection;

/**
 * @property int $id
 * @property string|null $name
 * @property string $key
 * @property int $type_id
 * @property int|null $site_id
 * @property int|null $language_id
 * @property DataCollection<int, NavigationItemData>|null $items
 * @property array<array-key, mixed>|null $meta
 * @property CarbonImmutable|null $visible_from
 * @property string|null $visible_until
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property CarbonImmutable|null $deleted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AuthenticatableUser|null $creator
 * @property-read AuthenticatableUser|null $destroyer
 * @property-read AuthenticatableUser|null $editor
 * @property-read Language|null $language
 * @property-read Site|null $site
 * @property-read Type $type
 *
 * @method static Builder<static>|Navigation excludeRevision(Model|int $exclude)
 * @method static Builder<static>|Navigation expired()
 * @method static NavigationFactory factory($count = null, $state = [])
 * @method static Builder<static>|Navigation newModelQuery()
 * @method static Builder<static>|Navigation newQuery()
 * @method static Builder<static>|Navigation onlyTrashed()
 * @method static Builder<static>|Navigation query()
 * @method static Builder<static>|Navigation whereCreatedAt($value)
 * @method static Builder<static>|Navigation whereCreatedBy($value)
 * @method static Builder<static>|Navigation whereDeletedAt($value)
 * @method static Builder<static>|Navigation whereDeletedBy($value)
 * @method static Builder<static>|Navigation whereId($value)
 * @method static Builder<static>|Navigation whereItems($value)
 * @method static Builder<static>|Navigation whereKey($value)
 * @method static Builder<static>|Navigation whereLanguageId($value)
 * @method static Builder<static>|Navigation whereMeta($value)
 * @method static Builder<static>|Navigation whereName($value)
 * @method static Builder<static>|Navigation whereVisibleFrom($value)
 * @method static Builder<static>|Navigation wherePublishTo($value)
 * @method static Builder<static>|Navigation whereSiteId($value)
 * @method static Builder<static>|Navigation whereTypeId($value)
 * @method static Builder<static>|Navigation whereUpdatedAt($value)
 * @method static Builder<static>|Navigation whereUpdatedBy($value)
 * @method static Builder<static>|Navigation withTrashed(bool $withTrashed = true)
 * @method static Builder<static>|Navigation withoutTrashed()
 *
 * @mixin Model
 */
#[ObservedBy(NavigationObserver::class)]
class Navigation extends Model implements PageCacheable, Publishable, Typeable, Userstampable
{
    use Cloneable;

    /** @use HasFactory<NavigationFactory> */
    use HasFactory;

    use HasMetaData;
    use HasPublishDates;
    use HasType;
    use HasUserstamps;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'items',
        'key',
        'language_id',
        'meta',
        'name',
        'visible_from',
        'visible_until',
        'site_id',
        'type_id',
    ];

    protected static string $factory = NavigationFactory::class;

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => DataCollection::class . ':' . NavigationItemData::class,
            'meta' => 'json',
            'visible_from' => 'datetime',
            'visible_until' => 'datetime',
        ];
    }
}
