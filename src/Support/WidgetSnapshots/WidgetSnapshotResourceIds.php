<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetSnapshots;

use Capell\Frontend\Data\Assets\FrontendResourceData;
use Capell\Frontend\Data\Assets\PublicResourceSourceData;
use Capell\Frontend\Enums\FrontendResourceKind;
use Capell\Frontend\Support\Assets\FrontendResourceRegistry;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;

final readonly class WidgetSnapshotResourceIds
{
    public function __construct(private FrontendResourceRegistry $registry) {}

    /** @param list<string> $declaredGroups
     * @return list<string>|null
     */
    public function resolve(array $declaredGroups): ?array
    {
        $ids = [];
        foreach ($declaredGroups as $groupKey) {
            $group = $this->registry->get($groupKey);
            if ($group === null || $group->resources === []) {
                return null;
            }

            foreach ($group->resources as $resource) {
                if (! $this->isSafe($resource)) {
                    return null;
                }
            }

            $ids[] = LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId($groupKey);
        }

        return array_values(array_unique($ids));
    }

    private function isSafe(FrontendResourceData $resource): bool
    {
        if (! in_array($resource->kind, [
            FrontendResourceKind::Style,
            FrontendResourceKind::ModuleScript,
            FrontendResourceKind::ClassicScript,
        ], true) || ! $resource->source instanceof PublicResourceSourceData) {
            return false;
        }

        $path = $resource->source->path;
        $decodedPath = rawurldecode($path);
        if (preg_match('/[\x00-\x20\x7f]/', $path) === 1 || in_array('..', explode('/', $decodedPath), true)) {
            return false;
        }

        $extension = strtolower(pathinfo($decodedPath, PATHINFO_EXTENSION));

        if ($resource->kind === FrontendResourceKind::Style) {
            return $extension === 'css';
        }

        return in_array($extension, ['js', 'mjs'], true);
    }
}
