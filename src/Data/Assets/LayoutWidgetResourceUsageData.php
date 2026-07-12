<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\Assets;

use Capell\Core\Data\Presentation\PresentationSettingsData;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Spatie\LaravelData\Data;

class LayoutWidgetResourceUsageData extends Data
{
    public function __construct(
        public string $widgetKey,
        public string $resourceGroup,
        public string $publicId,
        public PresentationSettingsData $presentation,
        public ?PresentationLoadingStrategy $loadingStrategy = null,
    ) {}
}
