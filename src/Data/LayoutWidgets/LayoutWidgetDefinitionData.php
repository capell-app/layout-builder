<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Data\LayoutWidgets;

use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Spatie\LaravelData\Data;

class LayoutWidgetDefinitionData extends Data
{
    /**
     * @param  array<int, string>  $resourceGroups
     * @param  array<string, mixed>  $defaultPresentationSettings
     * @param  array<int, array<string, mixed>>  $defaultInteractionTriggers
     * @param  array<string, PresentationLoadingStrategy>  $resourceGroupLoadingStrategies
     */
    public function __construct(
        public string $key,
        public LayoutWidgetTarget $target,
        public string $component,
        public array $resourceGroups = [],
        public array $defaultPresentationSettings = [],
        public array $defaultInteractionTriggers = [],
        public ?PresentationLoadingStrategy $defaultLoadingStrategy = null,
        public array $resourceGroupLoadingStrategies = [],
    ) {}

    /**
     * @param  array<int, string>  $resourceGroups
     * @param  array<string, mixed>  $defaultPresentationSettings
     * @param  array<int, array<string, mixed>>  $defaultInteractionTriggers
     * @param  array<string, PresentationLoadingStrategy>  $resourceGroupLoadingStrategies
     */
    public static function frontendBlade(
        string $key,
        string $component,
        array $resourceGroups = [],
        array $defaultPresentationSettings = [],
        array $defaultInteractionTriggers = [],
        ?PresentationLoadingStrategy $defaultLoadingStrategy = null,
        array $resourceGroupLoadingStrategies = [],
    ): self {
        return new self(
            key: $key,
            target: LayoutWidgetTarget::FrontendBlade,
            component: $component,
            resourceGroups: $resourceGroups,
            defaultPresentationSettings: $defaultPresentationSettings,
            defaultInteractionTriggers: $defaultInteractionTriggers,
            defaultLoadingStrategy: $defaultLoadingStrategy,
            resourceGroupLoadingStrategies: $resourceGroupLoadingStrategies,
        );
    }

    /**
     * @param  array<int, string>  $resourceGroups
     * @param  array<string, mixed>  $defaultPresentationSettings
     * @param  array<int, array<string, mixed>>  $defaultInteractionTriggers
     * @param  array<string, PresentationLoadingStrategy>  $resourceGroupLoadingStrategies
     */
    public static function frontendInertia(
        string $key,
        string $component,
        array $resourceGroups = [],
        array $defaultPresentationSettings = [],
        array $defaultInteractionTriggers = [],
        ?PresentationLoadingStrategy $defaultLoadingStrategy = null,
        array $resourceGroupLoadingStrategies = [],
    ): self {
        return new self(
            key: $key,
            target: LayoutWidgetTarget::FrontendInertia,
            component: $component,
            resourceGroups: $resourceGroups,
            defaultPresentationSettings: $defaultPresentationSettings,
            defaultInteractionTriggers: $defaultInteractionTriggers,
            defaultLoadingStrategy: $defaultLoadingStrategy,
            resourceGroupLoadingStrategies: $resourceGroupLoadingStrategies,
        );
    }
}
