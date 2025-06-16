<?php

declare(strict_types=1);

namespace Capell\Blog\Models;

use Capell\Core\Models\Page;
use Eloquent;

/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \OwenIt\Auditing\Models\Audit> $audits
 * @property-read int|null $audits_count
 * @property-read \Illuminate\Foundation\Auth\User|null $author
 * @property-read Page|null $canonicalPage
 * @property-read \Kalnoy\Nestedset\Collection<int, Page> $canonicalPages
 * @property-read int|null $canonical_pages_count
 * @property-read \Kalnoy\Nestedset\Collection<int, Article> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Foundation\Auth\User|null $creator
 * @property-read \Illuminate\Foundation\Auth\User|null $destroyer
 * @property-read \Kalnoy\Nestedset\Collection<int, Article> $draftRevisions
 * @property-read int|null $draft_revisions_count
 * @property-read \Illuminate\Foundation\Auth\User|null $editor
 * @property-read mixed $draft
 * @property-read bool $has_title_or_content
 * @property-read \Capell\Core\Enums\PublishStatusEnum $publish_status
 * @property-read Article|null $hasDraftsAndNestedSetParent
 * @property-read \Capell\Core\Models\Media|null $image
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Capell\Core\Models\Language> $languages
 * @property-read int|null $languages_count
 * @property-read \Capell\Core\Models\Layout|null $layout
 * @property-read Article|null $nodeTraitParent
 * @property-read \Capell\Core\Models\PageUrl $pageUrl
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Capell\Core\Models\PageUrl> $pageUrls
 * @property-read int|null $page_urls_count
 * @property-read Article|null $parent
 * @property-read Article|null $publishedPage
 * @property-read \Illuminate\Database\Eloquent\Model|Eloquent $publisher
 * @property-read \Kalnoy\Nestedset\Collection<int, Article> $revisions
 * @property-read int|null $revisions_count
 * @property-write mixed $parent_id
 * @property \Illuminate\Database\Eloquent\Collection<int, \Capell\Core\Models\Tag> $tags
 * @property-read \Kalnoy\Nestedset\Collection<int, Page> $siblings
 * @property-read int|null $siblings_count
 * @property-read \Capell\Core\Models\Site|null $site
 * @property-read int|null $tags_count
 * @property-read \Capell\Core\Models\PageTranslation|null $translation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Capell\Core\Models\PageTranslation> $translations
 * @property-read int|null $translations_count
 * @property-read \Capell\Core\Models\Type|null $type
 *
 * @method static \Kalnoy\Nestedset\Collection<int, static> all($columns = ['*'])
 * @method static QueryBuilder<static>|Article alphabetical(\Capell\Core\Models\Language $language, $direction = 'asc')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article ancestorsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article ancestorsOf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article applyNestedSetScope(?string $table = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article countErrors()
 * @method static QueryBuilder<static>|Article current()
 * @method static QueryBuilder<static>|Article d()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article defaultOrder(string $dir = 'asc')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article descendantsAndSelf($id, array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article descendantsOf($id, array $columns = [], $andSelf = false)
 * @method static QueryBuilder<static>|Article disabled()
 * @method static QueryBuilder<static>|Article enabled()
 * @method static QueryBuilder<static>|Article excludeRevision(\Illuminate\Database\Eloquent\Model|int $exclude)
 * @method static QueryBuilder<static>|Article expired(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Capell\Core\Database\Factories\PageFactory factory($count = null, $state = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article fixSubtree($root)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article fixTree($root = null)
 * @method static \Kalnoy\Nestedset\Collection<int, static> get($columns = ['*'])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article getNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article getPlainNodeData($id, $required = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article getTotalErrors()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article hasChildren()
 * @method static QueryBuilder<static>|Article hasImage()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article hasParent()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article isBroken()
 * @method static QueryBuilder<static>|Article isHomePage()
 * @method static QueryBuilder<static>|Article isNotHomePage()
 * @method static QueryBuilder<static>|Article latest()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article leaves(array $columns = [])
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article makeGap(int $cut, int $height)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article moveNode($key, $position)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article newModelQuery()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article onlyTrashed()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article orWhereAncestorOf(bool $id, bool $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article orWhereDescendantOf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article orWhereNodeBetween($values)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article orWhereNotDescendantOf($id)
 * @method static QueryBuilder<static>|Article ordered(string $dir = 'asc')
 * @method static QueryBuilder<static>|Article pending(\Illuminate\Database\Eloquent\Model $model)
 * @method static QueryBuilder<static>|Article published(\Illuminate\Database\Eloquent\Model $model)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article query()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article rebuildSubtree($root, array $data, $delete = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article rebuildTree(array $data, $delete = false, $root = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article reversed()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article root(array $columns = [])
 * @method static QueryBuilder<static>|Article status(bool $enabled)
 * @method static QueryBuilder<static>|Article visible()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article whereAncestorOf($id, $andSelf = false, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article whereAncestorOrSelf($id)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article whereDescendantOf($id, $boolean = 'and', $not = false, $andSelf = false)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article whereDescendantOrSelf(string $id, string $boolean = 'and', string $not = false)
 * @method static QueryBuilder<static>|Article whereHasLanguage(\Capell\Core\Models\Language $language)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article whereIsAfter($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article whereIsBefore($id, $boolean = 'and')
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article whereIsLeaf()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article whereIsRoot()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article whereNodeBetween($values, $boolean = 'and', $not = false, $query = null)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article whereNotDescendantOf($id)
 * @method static QueryBuilder<static>|Article withAllTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static QueryBuilder<static>|Article withAllTagsOfAnyType($tags)
 * @method static QueryBuilder<static>|Article withAnyTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static QueryBuilder<static>|Article withAnyTagsOfAnyType($tags)
 * @method static QueryBuilder<static>|Article withAnyTagsOfType(array|string $type)
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article withDepth(string $as = 'depth')
 * @method static QueryBuilder<static>|Article withResourceables(bool $withDrafts = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article withTrashed()
 * @method static QueryBuilder<static>|Article withWhereHasLanguage(int $language_id)
 * @method static QueryBuilder<static>|Article withoutCurrent()
 * @method static \Kalnoy\Nestedset\QueryBuilder<static>|Article withoutRoot()
 * @method static QueryBuilder<static>|Article withoutSelf()
 * @method static QueryBuilder<static>|Article withoutTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article withoutTrashed()
 *
 * @mixin \Eloquent
 * @mixin Eloquent
 */
class Article extends Page
{
    protected $table = 'pages';

    public function getForeignKey()
    {
        return 'page_'.$this->getKeyName();
    }
}
