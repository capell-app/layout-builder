<?php

declare(strict_types=1);

namespace Capell\Blog\Models;

use Bkwld\Cloner\Cloneable;
use Capell\Blog\Database\Factories\ArticleFactory;
use Capell\Blog\Enums\BlogPageTypeEnum;
use Capell\Blog\Models\Concerns\HasTags;
use Capell\Blog\Observers\ArticleObserver;
use Capell\Blog\Support\Loader\BlogLoader;
use Capell\Core\Contracts\Pageable;
use Capell\Core\Contracts\PageCacheable;
use Capell\Core\Enums\MediaCollectionEnum;
use Capell\Core\Enums\PageOrderEnum;
use Capell\Core\Models\Concerns\CloneableExcept;
use Capell\Core\Models\Concerns\HasAssets;
use Capell\Core\Models\Concerns\HasDrafts;
use Capell\Core\Models\Concerns\HasMetaData;
use Capell\Core\Models\Concerns\HasMorphModelRelations;
use Capell\Core\Models\Concerns\HasPageOrdering;
use Capell\Core\Models\Concerns\HasPublishDates;
use Capell\Core\Models\Concerns\HasTranslations;
use Capell\Core\Models\Concerns\HasType;
use Capell\Core\Models\Concerns\HasTypes;
use Capell\Core\Models\Concerns\HasUserstamps;
use Capell\Core\Models\Concerns\InteractsWithMedia;
use Capell\Core\Models\Contracts\Draftable;
use Capell\Core\Models\Contracts\Publishable;
use Capell\Core\Models\Contracts\Translatable;
use Capell\Core\Models\Contracts\Typeable;
use Capell\Core\Models\Contracts\Userstampable;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Staudenmeir\EloquentJsonRelations\Relations\BelongsToJson;

#[ObservedBy(ArticleObserver::class)]
class Article extends Model implements Draftable, HasMedia, Pageable, PageCacheable, Publishable, Translatable, Typeable, Userstampable
{
    use Cloneable;
    use CloneableExcept;
    use HasAssets;
    use HasDrafts;

    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    use HasJsonRelationships;
    use HasMetaData;
    use HasMorphModelRelations;
    use HasPageOrdering;
    use HasPublishDates;
    use HasTags;
    use HasTranslations;
    use HasType;
    use HasTypes;
    use HasUserstamps;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'articles';

    /**
     * @var array<string>
     */
    protected $fillable = [
        'is_published',
        'layout_id',
        'meta',
        'name',
        'order',
        'visible_from',
        'visible_until',
        'published_at',
        'site_id',
        'type_id',
        'uuid',
    ];

    protected array $clone_exempt_attributes = [
        'hidden',
    ];

    protected array $draftableRelations = [
        'translations',
    ];

    protected static string $factory = ArticleFactory::class;

    public static function getDefaultType(?string $group): ?Type
    {
        return Type::query()
            ->pageType()
            ->adminResource($group)
            ->where('key', BlogPageTypeEnum::Article->value)
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
        return (bool) ($this->type?->meta['disable_visit_logs'] ?? true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('article')
            ->logAll()
            ->logExcept([
                'updated_at',
                'created_at',
                'deleted_at',
                'draft_id',
                'is_published',
                'is_current',
                'publisher_type',
                'publisher_id',
                'created_by',
                'updated_by',
                'deleted_by',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaCollectionEnum::Image->value)->singleFile();
    }

    public function getParentUrl(Language $language, bool $fullUrl = false): string
    {
        $url = $fullUrl ? $this->site->getSiteDomainUrl($language) : '/';

        return $url . BlogLoader::getBlogPageUrl(site: $this->site, language: $language, fullUrl: $fullUrl);
    }

    public function getPublishDate(): ?CarbonImmutable
    {
        $date = $this->published_at ?? $this->visible_from ?? $this->created_at;

        return $date !== null ? CarbonImmutable::make($date) : null;
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return MorphOne<PageUrl, self> */
    public function pageUrl(): MorphOne
    {
        return $this->morphOne(PageUrl::class, 'pageable')->withDefault(['site_id' => $this->site_id]);
    }

    /** @return MorphMany<PageUrl, self> */
    public function pageUrls(): MorphMany
    {
        $model = $this->morphMany(PageUrl::class, 'pageable');

        if (method_exists($model, 'chaperone')) {
            $model->chaperone('pageable');
        }

        return $model;
    }

    /** @return MorphMany<Article, self> */
    public function canonicalPages(): MorphMany
    {
        return $this->morphMany(
            self::class,
            'canonical_pageable',
            'meta->canonical_pageable_type',
            'meta->canonical_pageable_id',
        );
    }

    public function image(): MorphOne
    {
        return $this->morphOneMedia(MediaCollectionEnum::Image->value);
    }

    /**
     * @return BelongsToJson<Article, self>
     */
    public function related(): BelongsToJson
    {
        return $this->belongsToJson(self::class, 'meta->related');
    }

    public function canonicalPage(): MorphTo
    {
        return $this->morphTo(type: 'meta->canonical_pageable_type', id: 'meta->canonical_pageable_id');
    }

    /** @return Builder<self> */
    public function nextSiblings(): Builder
    {
        $effectivePublishDateExpression = $this->effectivePublishDateExpression();
        $currentPublishDate = $this->visible_from ?? $this->created_at;

        return self::query()
            ->whereKeyNot($this->getKey())
            ->where('site_id', $this->site_id)
            ->where(function (Builder $query) use ($effectivePublishDateExpression, $currentPublishDate): void {
                $query->whereRaw($effectivePublishDateExpression . ' > ?', [$currentPublishDate])
                    ->orWhere(function (Builder $query) use ($effectivePublishDateExpression, $currentPublishDate): void {
                        $query->whereRaw($effectivePublishDateExpression . ' = ?', [$currentPublishDate])
                            ->where('id', '>', $this->getKey());
                    });
            })
            ->orderByRaw($effectivePublishDateExpression . ' asc')
            ->orderBy('id');
    }

    /** @return Builder<self> */
    public function prevSiblings(): Builder
    {
        $effectivePublishDateExpression = $this->effectivePublishDateExpression();
        $currentPublishDate = $this->visible_from ?? $this->created_at;

        return self::query()
            ->whereKeyNot($this->getKey())
            ->where('site_id', $this->site_id)
            ->where(function (Builder $query) use ($effectivePublishDateExpression, $currentPublishDate): void {
                $query->whereRaw($effectivePublishDateExpression . ' < ?', [$currentPublishDate])
                    ->orWhere(function (Builder $query) use ($effectivePublishDateExpression, $currentPublishDate): void {
                        $query->whereRaw($effectivePublishDateExpression . ' = ?', [$currentPublishDate])
                            ->where('id', '<', $this->getKey());
                    });
            })
            ->orderByRaw($effectivePublishDateExpression . ' desc')
            ->orderBy('id', 'desc');
    }

    /** @return array<string, mixed>|null */
    protected function getUrlParamsAttribute(): ?array
    {
        return $this->type->meta['url_params'] ?? null;
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'meta' => 'json',
            'visible_from' => 'datetime',
            'visible_until' => 'datetime',
        ];
    }

    private function effectivePublishDateExpression(): string
    {
        return sprintf('COALESCE(%s, %s)', $this->qualifyColumn('visible_from'), $this->qualifyColumn('created_at'));
    }
}
