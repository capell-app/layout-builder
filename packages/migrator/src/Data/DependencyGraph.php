<?php

declare(strict_types=1);

namespace Capell\Migrator\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * Everything that must travel together to reconstruct a set of pages in
 * another environment.
 *
 * Pages and sites are always roots. Shared relations are collected per type
 * and keyed by the stable `ref` the importer uses to wire them back up.
 * Media references are collected separately so the writer can copy binaries.
 */
final readonly class DependencyGraph
{
    /**
     * @param  array<int, Model>  $pages
     * @param  array<int, Model>  $sites
     * @param  array<string, array<string, Model>>  $sharedRelations  type => [ref => model]
     * @param  array<string, array{path: string, checksum: string, model: Model}>  $media  ref => descriptor
     */
    public function __construct(
        public array $pages,
        public array $sites,
        public array $sharedRelations,
        public array $media,
    ) {}

    public function pageCount(): int
    {
        return count($this->pages);
    }

    public function siteCount(): int
    {
        return count($this->sites);
    }

    /**
     * @return array<string, int>
     */
    public function sharedRelationCounts(): array
    {
        $counts = [];

        foreach ($this->sharedRelations as $type => $models) {
            $counts[$type] = count($models);
        }

        if ($this->media !== []) {
            $counts['media'] = count($this->media);
        }

        return $counts;
    }
}
