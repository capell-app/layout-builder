<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data;

use Capell\Core\Data\Interactions\InteractionTriggerData;
use Capell\Core\Data\Presentation\PresentationSettingsData;
use Spatie\LaravelData\Data;

final class PublicWidgetRenderContextData extends Data
{
    /**
     * @param  list<string>  $resourcePublicIds
     * @param  array<int, InteractionTriggerData>  $interactions
     */
    public function __construct(
        public int $occurrence,
        public string $widgetDomId,
        public PresentationSettingsData $presentation,
        public bool $isLazyFragment,
        public ?string $widgetReference,
        public ?string $fragmentUrl,
        public array $resourcePublicIds,
        public array $interactions,
    ) {}
}
