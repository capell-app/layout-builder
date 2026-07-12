<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetSnapshots;

use JsonException;

final class WidgetSnapshotFingerprint
{
    /** @param array<string, mixed> $widget */
    public function make(
        int $siteId,
        string $pageableType,
        int $pageableId,
        int $languageId,
        ?int $layoutId,
        ?int $themeId,
        string $renderProfile,
        string $ownerRevision,
        string $instanceId,
        int $definitionVersion,
        array $widget,
    ): string {
        try {
            $encoded = json_encode([
                'site' => $siteId,
                'pageable_type' => $pageableType,
                'pageable_id' => $pageableId,
                'language' => $languageId,
                'layout' => $layoutId,
                'theme' => $themeId,
                'profile' => $renderProfile,
                'revision' => $ownerRevision,
                'instance' => $instanceId,
                'definition_version' => $definitionVersion,
                'widget' => $widget,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        } catch (JsonException) {
            $encoded = serialize([$siteId, $pageableType, $pageableId, $languageId, $layoutId, $themeId, $renderProfile, $ownerRevision, $instanceId, $definitionVersion, $widget]);
        }

        return hash('sha256', $encoded);
    }
}
