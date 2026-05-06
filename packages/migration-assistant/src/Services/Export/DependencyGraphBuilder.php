<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Services\Export;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Type;
use Capell\MigrationAssistant\Data\DependencyGraph;
use Capell\MigrationAssistant\Data\ExportOptions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Walks a set of root pages/sites and returns everything required to
 * reconstruct them in another environment.
 *
 * The walk is conservative by design: owned relations always come along,
 * shared relations come along only when export options permit it, and
 * media binaries are enumerated here but copied by the PackageWriter.
 */
final class DependencyGraphBuilder
{
    /**
     * @param  Collection<int, Page>  $pages
     * @param  Collection<int, Site>  $sites
     */
    public function build(Collection $pages, Collection $sites, ExportOptions $options): DependencyGraph
    {
        $pages->loadMissing(['pageUrls']);

        /** @var array<string, array<string, Model>> $shared */
        $shared = [];

        if ($options->includeSharedRelations) {
            $this->collectSites($pages, $sites, $shared);
            $this->collectLayouts($pages, $shared);
            $this->collectTypes($pages, $shared);
        } else {
            // Sites are always required roots when explicitly selected.
            foreach ($sites as $site) {
                $shared[Site::class][$this->refFor(Site::class, $site)] = $site;
            }
        }

        $media = $options->includeMedia ? $this->collectMedia($pages) : [];

        return new DependencyGraph(
            pages: $pages->all(),
            sites: $sites->all(),
            sharedRelations: $shared,
            media: $media,
        );
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @param  Collection<int, Site>  $sites
     * @param  array<string, array<string, Model>>  $shared
     */
    private function collectSites(Collection $pages, Collection $sites, array &$shared): void
    {
        $siteMap = [];

        foreach ($sites as $site) {
            $siteMap[$site->getKey()] = $site;
        }

        foreach ($pages as $page) {
            $site = $page->getAttribute('site_id') !== null
                ? ($page->relationLoaded('site') ? $page->getRelation('site') : null)
                : null;

            if ($site instanceof Site) {
                $siteMap[$site->getKey()] = $site;
            }
        }

        foreach ($siteMap as $site) {
            /** @var Site $site */
            $shared[Site::class][$this->refFor(Site::class, $site)] = $site;

            $site->loadMissing('siteDomains');

            foreach ($site->getRelation('siteDomains') as $domain) {
                /** @var SiteDomain $domain */
                $shared[SiteDomain::class][$this->refFor(SiteDomain::class, $domain)] = $domain;
            }
        }
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @param  array<string, array<string, Model>>  $shared
     */
    private function collectLayouts(Collection $pages, array &$shared): void
    {
        $layoutIds = $pages
            ->pluck('layout_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($layoutIds === []) {
            return;
        }

        $layouts = Layout::query()->whereIn('id', $layoutIds)->get();

        foreach ($layouts as $layout) {
            $shared[Layout::class][$this->refFor(Layout::class, $layout)] = $layout;
        }
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @param  array<string, array<string, Model>>  $shared
     */
    private function collectTypes(Collection $pages, array &$shared): void
    {
        $typeIds = $pages
            ->pluck('type_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($typeIds === []) {
            return;
        }

        $types = Type::query()->whereIn('id', $typeIds)->get();

        foreach ($types as $type) {
            $shared[Type::class][$this->refFor(Type::class, $type)] = $type;
        }
    }

    /**
     * @param  Collection<int, Page>  $pages
     * @return array<string, array{path: string, checksum: string, model: Model}>
     */
    private function collectMedia(Collection $pages): array
    {
        $descriptors = [];

        foreach ($pages as $page) {
            $page->loadMissing('media');

            foreach ($page->getRelation('media') as $mediaRow) {
                /** @var Media $mediaRow */
                $ref = $this->refFor(Media::class, $mediaRow);

                if (isset($descriptors[$ref])) {
                    continue;
                }

                $disk = $mediaRow->disk ?? 'public';
                $path = sprintf('%s/%s', $mediaRow->id, $mediaRow->file_name);
                $absolutePath = Storage::disk($disk)->path($path);

                if (! is_file($absolutePath)) {
                    continue;
                }

                $descriptors[$ref] = [
                    'path' => $absolutePath,
                    'checksum' => 'sha256-' . hash_file('sha256', $absolutePath),
                    'model' => $mediaRow,
                ];
            }
        }

        return $descriptors;
    }

    /**
     * @param  class-string  $class
     */
    private function refFor(string $class, Model $model): string
    {
        $slug = match ($class) {
            Site::class => 'site',
            SiteDomain::class => 'site-domain',
            Layout::class => 'layout',
            Type::class => 'type',
            Media::class => 'media',
            default => strtolower(class_basename($class)),
        };

        $attributes = $model->getAttributes();
        $key = $attributes['uuid'] ?? $attributes['key'] ?? $attributes['slug'] ?? $model->getKey();

        return sprintf('%s:%s', $slug, (string) $key);
    }
}
