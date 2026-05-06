<?php

declare(strict_types=1);

namespace Capell\FrontendAuthoring\Actions;

use Capell\Core\Models\PageUrl;
use Capell\FrontendAuthoring\Data\EditableRegionData;
use Capell\FrontendAuthoring\Support\EditableRegionRegistry;
use Capell\FrontendAuthoring\Support\EditableRegionSigner;
use Lorisleiva\Actions\Concerns\AsObject;

class BuildEditableRegionManifestAction
{
    use AsObject;

    /**
     * @return array<string, array<string, string>>
     */
    public function handle(PageUrl $pageUrl): array
    {
        $registry = resolve(EditableRegionRegistry::class);
        $signer = resolve(EditableRegionSigner::class);
        $manifest = [];

        foreach ($registry->regionsFor($pageUrl) as $payload) {
            $region = new EditableRegionData(
                id: $signer->idFor($payload),
                label: $payload->label,
                type: $payload->type,
                selector: $payload->selector,
                editUrl: $signer->signedEditUrl($payload),
            );

            $manifest[$region->id] = $region->toArray();
        }

        return $manifest;
    }
}
