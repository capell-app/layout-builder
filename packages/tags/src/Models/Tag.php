<?php

declare(strict_types=1);

namespace Capell\Tags\Models;

use Aimeos\Nestedset\Collection;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Models\Concerns\HasStatus;
use Capell\Core\Models\Contracts\Statusable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Tags\Database\Factories\TagFactory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\DB;
use Override;

/**
 * @property int $id
 * @property array<array-key, mixed> $name
 * @property array<array-key, mixed> $slug
 * @property string|null $type
 * @property int|null $order_column
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property bool $featured
 * @property bool $status
 * @property int|null $site_id
 * @property-read Collection<int, Page> $pages
 * @property-read Collection<int, Taggable> $taggables
 * @property-read int|null $pages_count
 * @property-read int|null $taggables_count
 * @property-read Site|null $site
 * @property-read mixed $translations
 *
 * @method static Builder<static>|Tag containing(string $name, $locale = null)
 * @method static Builder<static>|Tag disabled()
 * @method static Builder<static>|Tag enabled()
 * @method static TagFactory factory($count = null, $state = [])
 * @method static Builder<static>|Tag newModelQuery()
 * @method static Builder<static>|Tag newQuery()
 * @method static Builder<static>|Tag ordered(string $direction = 'asc', $locale = null)
 * @method static Builder<static>|Tag query()
 * @method static Builder<static>|Tag whereJsonContainsLocale(string $column, string $locale, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Tag whereJsonContainsLocales(string $column, array $locales, ?mixed $value, string $operand = '=')
 * @method static Builder<static>|Tag whereLocale(string $column, string $locale)
 * @method static Builder<static>|Tag whereLocales(string $column, array $locales)
 * @method static Builder<static>|Tag withTranslatedLocales(string $key)
 * @method static Builder<static>|Tag withType(?string $type = null)
 * @method static Builder<static>|Tag status(bool $enabled)
 *
 * @mixin Model
 */
class Tag extends \Spatie\Tags\Tag implements PageCacheable, Statusable
{
    use HasStatus;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'featured',
        'meta',
        'name',
        'order_column',
        'site_id',
        'slug',
        'status',
        'type',
    ];

    protected static string $factory = TagFactory::class;

    #[Override]
    public static function findOrCreateFromString(string $name, ?string $type = null, ?string $locale = null)
    {
        $locale ??= static::getLocale();

        $tag = static::findFromString($name, $type, $locale);

        if (! $tag) {
            $defaultLocale = static::getLocale();

            if ($locale !== $defaultLocale) {
                $tag = static::findFromString($name, $type, $defaultLocale);
            }

            if (! $tag) {
                $tag = static::query()->create([
                    'name' => [$locale => $name],
                    'slug' => [$locale => str($name)->slug()],
                    'type' => $type,
                ]);
            }
        }

        return $tag;
    }

    public function getTranslatedLocales(string $key): array
    {
        return Language::getLanguageLocales();
    }

    public function getUrl(Page $tagPage, Language $language): string
    {
        return $tagPage->pageUrl->full_url . '/' . $this->translate('slug', $language->code);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function pages(): MorphToMany
    {
        return $this->morphedByMany(Page::class, 'taggable');
    }

    /**
     * Access the raw taggable pivot records for this tag.
     *
     * This returns the pivot rows from the `taggables` table so callers can
     * inspect which models (type + id) are associated with this tag. For
     * convenience use the morph-specific relations like `pages()` when you
     * need the hydrated models. Consumer packages (blog, mosaic) register
     * their own morph relations via Tag::resolveRelationUsing().
     */
    public function taggables(): HasMany
    {
        return $this->hasMany(Taggable::class, 'tag_id', 'id');
    }

    public function scopeOrdered(Builder $query, string $direction = 'asc', ?string $locale = null): void
    {
        $locale ??= static::getLocale();

        $query->orderBy($this->determineOrderColumnName(), $direction);

        $query->orderByRaw($this->getQuery()->getGrammar()->wrap('name->' . $locale) . ' ' . $direction);
    }

    public function shouldSortWhenCreating(): bool
    {
        return false;
    }

    public function getFirstTranslationLocale(string $key): ?string
    {
        $locales = $this->getTranslatedLocales($key);

        foreach ($locales as $locale) {
            if ($this->hasTranslation($key, $locale)) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * @param  string  $key
     */
    public function getAttributeValue($key): mixed
    {
        if (! $this->isTranslatableAttribute($key)) {
            return parent::getAttributeValue($key);
        }

        $value = $this->getTranslation($key, $this->getLocale(), $this->useFallbackLocale());

        if (blank($value)) {
            $locale = $this->getFirstTranslationLocale($key);

            if (! in_array($locale, [null, '', '0'], true)) {
                $value = $this->getTranslation($key, $locale, false);
            }
        }

        return $value;
    }

    protected function scopeWithTranslatedLocales(Builder $query, string $key): Builder
    {
        return $query->addSelect(
            DB::raw(
                $this->getConnection()->getDriverName() === 'sqlite'
                    ? 'NULL as translated_locales'
                    : 'JSON_KEYS(' . $this->getQuery()->getGrammar()->wrap($key) . ') as translated_locales',
            ),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'json',
            'featured' => 'boolean',
            'status' => 'boolean',
        ];
    }
}
