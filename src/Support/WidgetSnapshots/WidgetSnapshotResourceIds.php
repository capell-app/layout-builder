<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetSnapshots;

use Capell\Frontend\Data\Assets\FrontendResourceData;
use Capell\Frontend\Support\Assets\FrontendResourceRegistry;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Illuminate\Support\Str;

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
            if ($group === null || ! $group->validation->valid || $group->resources === []) {
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
        if (! in_array($resource->kind, ['css', 'js'], true)) {
            return false;
        }

        $source = trim($resource->source);
        if ($source === ''
            || preg_match('/[\x00-\x20\x7f]/', $source) === 1
            || Str::startsWith(strtolower($source), ['data:', 'javascript:', 'blob:'])
            || Str::contains($source, ['<', '>', '\\'])) {
            return false;
        }

        $path = parse_url($source, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return false;
        }
        $decodedPath = rawurldecode($path);
        if (in_array('..', explode('/', $decodedPath), true)) {
            return false;
        }
        $extension = strtolower(pathinfo($decodedPath, PATHINFO_EXTENSION));
        if ($resource->kind === 'css' && $extension !== 'css') {
            return false;
        }
        if ($resource->kind === 'js' && ! in_array($extension, ['js', 'mjs'], true)) {
            return false;
        }

        $scheme = parse_url($source, PHP_URL_SCHEME);
        if ($scheme === null) {
            return ! str_starts_with($source, '//');
        }

        $host = parse_url($source, PHP_URL_HOST);
        $port = parse_url($source, PHP_URL_PORT);
        $normalizedScheme = strtolower((string) $scheme);
        $effectivePort = is_int($port) ? $port : ($normalizedScheme === 'https' ? 443 : 80);

        return in_array($normalizedScheme, ['http', 'https'], true)
            && is_string($host)
            && hash_equals(strtolower(request()->getScheme()), $normalizedScheme)
            && hash_equals(strtolower(request()->getHost()), strtolower($host))
            && request()->getPort() === $effectivePort;
    }
}
