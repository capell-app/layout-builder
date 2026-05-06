<?php

declare(strict_types=1);

namespace Capell\MigrationAssistant\Services\Import\Resolvers;

use Capell\Core\Models\Media;

/**
 * Match media by content checksum first, then by file name. Checksum
 * lookup lets a package reuse an existing upload even if its filename
 * has drifted; the filename fallback covers older exports that did not
 * carry a checksum column.
 */
final class MediaMatchResolver implements MatchResolver
{
    public function resolve(array $descriptor): ?MatchResolution
    {
        $checksum = $descriptor['checksum'] ?? null;
        if (is_string($checksum) && $checksum !== '') {
            $model = Media::query()
                ->where('custom_properties->checksum', $checksum)
                ->first();
            if ($model instanceof Media) {
                return new MatchResolution(localId: $model->getKey(), strategy: 'checksum');
            }
        }

        $fileName = $descriptor['file_name'] ?? null;
        if (is_string($fileName) && $fileName !== '') {
            $model = Media::query()->where('file_name', $fileName)->first();
            if ($model instanceof Media) {
                return new MatchResolution(
                    localId: $model->getKey(),
                    strategy: 'file_name',
                    confidence: 0.6,
                );
            }
        }

        return null;
    }
}
