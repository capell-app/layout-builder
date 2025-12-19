<?php

declare(strict_types=1);

namespace Capell\Blog\Models;

use ArrayAccess;
use Capell\Blog\Models\Concerns\HasTags;
use Capell\Core\Database\Factories\PageFactory;
use Capell\Core\Enums\PublishStatusEnum;
use Capell\Core\Models\AssetRelation;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout;
use Capell\Core\Models\Page;
use Capell\Core\Models\PageTranslation;
use Capell\Core\Models\PageUrl;
use Capell\Core\Models\Site;
use Capell\Core\Models\Type;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Kalnoy\Nestedset\QueryBuilder;
use Override;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Tags\Tag;

/**
 * @property-read int|null $audits_count
 * @property-read User|null $author
 * @property-read Page|null $canonicalPage
 * @property-read \Kalnoy\Nestedset\Collection<int, Page> $canonicalPages
 * @property-read int|null $canonical_pages_count
 * @property-read \Kalnoy\Nestedset\Collection<int, Article> $children
 * @property-read int|null $children_count
 * @property-read User|null $creator
 * @property-read User|null $destroyer
 * @property-read \Kalnoy\Nestedset\Collection<int, Article> $draftRevisions
 * @property-read int|null $draft_revisions_count
 * @property-read User|null $editor
 * @property-read mixed $draft
 * @property-read bool $has_title_or_content
 * @property-read PublishStatusEnum $publish_status
 * @property-read Article|null $hasDraftsAndNestedSetParent
 * @property-read Media|null $image
 * @property-read Collection<int, Language> $languages
 * @property-read int|null $languages_count
 * @property-read Layout|null $layout
 * @property-read Article|null $nodeTraitParent
 * @property-read PageUrl $pageUrl
 * @property-read Collection<int, PageUrl> $pageUrls
 * @property-read int|null $page_urls_count
 * @property-read Article|null $parent
 * @property-read Article|null $publishedPage
 * @property-read Model $publisher
 * @property-read \Kalnoy\Nestedset\Collection<int, Article> $revisions
 * @property-read int|null $revisions_count
 * @property-write mixed $parent_id
 * @property Collection<int, Tag> $tags
 * @property-read \Kalnoy\Nestedset\Collection<int, Page> $siblings
 * @property-read int|null $siblings_count
 * @property-read Site|null $site
 * @property-read int|null $tags_count
 * @property-read PageTranslation|null $translation
 * @property-read Collection<int, PageTranslation> $translations
 * @property-read int|null $translations_count
 * @property-read Type|null $type
 *
 * @method static \Kalnoy\Nestedset\Collection<int, static> all($columns = ['*'])
 * @method static QueryBuilder<static>|Article alphabetical(Language $language, $direction = 'asc')
 * @method static QueryBuilder<static>|Article ancestorsAndSelf($id, array $columns = [])
 * @method static QueryBuilder<static>|Article ancestorsOf($id, array $columns = [])
 * @method static QueryBuilder<static>|Article applyNestedSetScope(?string $table = null)
 * @method static QueryBuilder<static>|Article countErrors()
 * @method static QueryBuilder<static>|Article current()
 * @method static QueryBuilder<static>|Article d()
 * @method static QueryBuilder<static>|Article defaultOrder(string $dir = 'asc')
 * @method static QueryBuilder<static>|Article descendantsAndSelf($id, array $columns = [])
 * @method static QueryBuilder<static>|Article descendantsOf($id, array $columns = [], $andSelf = false)
 * @method static QueryBuilder<static>|Article disabled()
 * @method static QueryBuilder<static>|Article enabled()
 * @method static QueryBuilder<static>|Article excludeRevision((Model|int) $exclude)
 * @method static QueryBuilder<static>|Article expired(Model $model)
 * @method static PageFactory factory($count = null, $state = [])
 * @method static QueryBuilder<static>|Article fixSubtree($root)
 * @method static QueryBuilder<static>|Article fixTree($root = null)
 * @method static \Kalnoy\Nestedset\Collection<int, static> get($columns = ['*'])
 * @method static QueryBuilder<static>|Article getNodeData($id, $required = false)
 * @method static QueryBuilder<static>|Article getPlainNodeData($id, $required = false)
 * @method static QueryBuilder<static>|Article getTotalErrors()
 * @method static QueryBuilder<static>|Article hasChildren()
 * @method static QueryBuilder<static>|Article hasImage()
 * @method static QueryBuilder<static>|Article hasParent()
 * @method static QueryBuilder<static>|Article isBroken()
 * @method static QueryBuilder<static>|Article homePage()
 * @method static QueryBuilder<static>|Article notHomePage()
 * @method static QueryBuilder<static>|Article latest()
 * @method static QueryBuilder<static>|Article leaves(array $columns = [])
 * @method static QueryBuilder<static>|Article makeGap(int $cut, int $height)
 * @method static QueryBuilder<static>|Article moveNode($key, $position)
 * @method static QueryBuilder<static>|Article newModelQuery()
 * @method static QueryBuilder<static>|Article newQuery()
 * @method static Builder<static>|Article onlyTrashed()
 * @method static QueryBuilder<static>|Article orWhereAncestorOf(bool $id, bool $andSelf = false)
 * @method static QueryBuilder<static>|Article orWhereDescendantOf($id)
 * @method static QueryBuilder<static>|Article orWhereNodeBetween($values)
 * @method static QueryBuilder<static>|Article orWhereNotDescendantOf($id)
 * @method static QueryBuilder<static>|Article ordered(string $dir = 'asc')
 * @method static QueryBuilder<static>|Article pending(Model $model)
 * @method static QueryBuilder<static>|Article published(Model $model)
 * @method static QueryBuilder<static>|Article query()
 * @method static QueryBuilder<static>|Article rebuildSubtree($root, array $data, $delete = false)
 * @method static QueryBuilder<static>|Article rebuildTree(array $data, $delete = false, $root = null)
 * @method static QueryBuilder<static>|Article reversed()
 * @method static QueryBuilder<static>|Article root(array $columns = [])
 * @method static QueryBuilder<static>|Article status(bool $enabled)
 * @method static QueryBuilder<static>|Article visible()
 * @method static QueryBuilder<static>|Article whereAncestorOf($id, $andSelf = false, $boolean = 'and')
 * @method static QueryBuilder<static>|Article whereAncestorOrSelf($id)
 * @method static QueryBuilder<static>|Article whereDescendantOf($id, $boolean = 'and', $not = false, $andSelf = false)
 * @method static QueryBuilder<static>|Article whereDescendantOrSelf(string $id, string $boolean = 'and', string $not = false)
 * @method static QueryBuilder<static>|Article whereHasLanguage(Language $language)
 * @method static QueryBuilder<static>|Article whereIsAfter($id, $boolean = 'and')
 * @method static QueryBuilder<static>|Article whereIsBefore($id, $boolean = 'and')
 * @method static QueryBuilder<static>|Article whereIsLeaf()
 * @method static QueryBuilder<static>|Article whereIsRoot()
 * @method static QueryBuilder<static>|Article whereNodeBetween($values, $boolean = 'and', $not = false, $query = null)
 * @method static QueryBuilder<static>|Article whereNotDescendantOf($id)
 * @method static QueryBuilder<static>|Article withAllTags((ArrayAccess|Tag|array|string) $tags, ?string $type = null)
 * @method static QueryBuilder<static>|Article withAllTagsOfAnyType($tags)
 * @method static QueryBuilder<static>|Article withAnyTags((ArrayAccess|Tag|array|string) $tags, ?string $type = null)
 * @method static QueryBuilder<static>|Article withAnyTagsOfAnyType($tags)
 * @method static QueryBuilder<static>|Article withAnyTagsOfType(array|string $type)
 * @method static QueryBuilder<static>|Article withDepth(string $as = 'depth')
 * @method static QueryBuilder<static>|Article withAssets(bool $withDrafts = true)
 * @method static Builder<static>|Article withTrashed()
 * @method static QueryBuilder<static>|Article withWhereHasLanguage(int $language_id)
 * @method static QueryBuilder<static>|Article withoutCurrent()
 * @method static QueryBuilder<static>|Article withoutRoot()
 * @method static QueryBuilder<static>|Article withoutSelf()
 * @method static QueryBuilder<static>|Article withoutTags((ArrayAccess|Tag|array|string) $tags, ?string $type = null)
 * @method static Builder<static>|Article withoutTrashed()
 *
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read Collection<int, AssetRelation> $assetRelations
 * @property-read int|null $asset_relations_count
 * @property-read Collection<int, AssetRelation> $assets
 * @property-read int|null $assets_count
 * @property-read Collection<int, Activity> $activities
 * @property-read int|null $activities_count
 * @property-read Collection|Page[] $related
 * @property-read int|null $related_count
 * @property-read string|null $title
 *
 * @mixin Model
 */
class Article extends Page
{
    use HasTags;

    protected $table = 'pages';

    #[Override]
    public function getForeignKey()
    {
        return 'page_' . $this->getKeyName();
    }
}
