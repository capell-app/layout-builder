<?php

declare(strict_types=1);

namespace Capell\Migrator\Services\Export;

use Capell\Core\Models\Layout;
use Capell\Core\Models\Media;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Core\Models\SiteDomain;
use Capell\Core\Models\Type;
use Capell\Core\Support\Json\JsonCodec;
use Capell\Migrator\Contracts\MigratorRowContributor;
use Capell\Migrator\Contracts\NullMigratorRowContributor;
use Capell\Migrator\Data\DependencyGraph;
use Illuminate\Database\Eloquent\Model;
use JsonException;

/**
 * Turns a DependencyGraph into a deterministic in-memory package payload
 * keyed by archive path: ['pages/{uuid}.json' => '...', 'relations/...' => '...'].
 *
 * Shape is stable so the importer can read it back and so archive checksums
 * compare byte-for-byte across identical exports.
 */
final readonly class PayloadSerializer
{
    public function __construct(
        private MigratorRowContributor $rowContributor = new NullMigratorRowContributor,
    ) {}

    /**
     * @return array<string, string>
     */
    public function serialize(DependencyGraph $graph): array
    {
        $payload = [];

        foreach ($graph->pages as $page) {
            /** @var Page $page */
            $payload[sprintf('pages/%s.json', $this->stableKey($page))] = $this->encode($this->serializePage($page));
        }

        foreach ($graph->sharedRelations as $class => $models) {
            $folder = $this->folderFor($class);

            foreach ($models as $ref => $model) {
                $relPath = sprintf('relations/%s/%s.json', $folder, $this->stableKeyFromRef($ref));
                $checksum = $graph->media[$ref]['checksum'] ?? null;
                $payload[$relPath] = $this->encode($this->serializeSharedRelation($class, $ref, $model, is_string($checksum) ? $checksum : null));
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePage(Page $page): array
    {
        return [
            'type' => 'page',
            'uuid' => $this->stableKey($page),
            'id' => $page->getKey(),
            'source' => [
                'site_id' => $page->getAttribute('site_id'),
                ...$this->rowContributor->extraAttributes($page),
            ],
            'attributes' => $page->attributesToArray(),
            'owned_relations' => [
                'page_urls' => $page->relationLoaded('pageUrls')
                    ? $page->getRelation('pageUrls')->map(fn (Model $url): array => $url->attributesToArray())->all()
                    : [],
            ],
            'shared_relations' => array_filter([
                'layout' => $page->getAttribute('layout_id') === null
                    ? null
                    : ['ref' => 'layout:' . $page->getAttribute('layout_id')],
                'type' => $page->getAttribute('type_id') === null
                    ? null
                    : ['ref' => 'type:' . $page->getAttribute('type_id')],
                'site' => $page->getAttribute('site_id') === null
                    ? null
                    : ['ref' => 'site:' . $page->getAttribute('site_id')],
            ]),
            'media_bindings' => $page->relationLoaded('media')
                ? $page->getRelation('media')->map(fn (Model $media): array => [
                    'collection' => $media->getAttribute('collection_name'),
                    'ref' => 'media:' . $media->getKey(),
                ])->all()
                : [],
        ];
    }

    /**
     * @param  class-string  $class
     * @return array<string, mixed>
     */
    private function serializeSharedRelation(string $class, string $ref, Model $model, ?string $checksum = null): array
    {
        $base = [
            'type' => $this->typeLabelFor($class),
            'ref' => $ref,
            'id' => $model->getKey(),
            'attributes' => $model->attributesToArray(),
        ];

        if ($model instanceof Media) {
            $base['file_name'] = $model->getAttribute('file_name');
            $base['mime_type'] = $model->getAttribute('mime_type');
            $base['size'] = $model->getAttribute('size');
            $collection = $model->getAttribute('collection_name');
            $base['collection_name'] = is_string($collection) ? $collection : 'default';
            if ($checksum !== null) {
                $base['checksum'] = $checksum;
            }
        }

        return $base;
    }

    /**
     * @param  class-string  $class
     */
    private function folderFor(string $class): string
    {
        return match ($class) {
            Site::class => 'sites',
            SiteDomain::class => 'site-domains',
            Layout::class => 'layouts',
            Type::class => 'types',
            Media::class => 'media',
            default => strtolower(class_basename($class)) . 's',
        };
    }

    /**
     * @param  class-string  $class
     */
    private function typeLabelFor(string $class): string
    {
        return match ($class) {
            Site::class => 'site',
            SiteDomain::class => 'site-domain',
            Layout::class => 'layout',
            Type::class => 'type',
            Media::class => 'media',
            default => strtolower(class_basename($class)),
        };
    }

    private function stableKey(Model $model): string
    {
        $attributes = $model->getAttributes();

        return (string) ($attributes['uuid'] ?? $model->getKey());
    }

    private function stableKeyFromRef(string $ref): string
    {
        $colon = strpos($ref, ':');

        return $colon === false ? $ref : substr($ref, $colon + 1);
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws JsonException
     */
    private function encode(array $data): string
    {
        return JsonCodec::encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
