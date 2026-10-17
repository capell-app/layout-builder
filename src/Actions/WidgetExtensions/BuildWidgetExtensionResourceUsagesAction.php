<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetExtensions;

use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\Frontend\Data\Assets\FrontendWidgetResourceUsageData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildWidgetExtensionResourceUsagesAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<string, mixed>  $block
     * @return list<FrontendWidgetResourceUsageData>
     */
    public function handle(WidgetExtensionDefinitionData $definition, array $block): array
    {
        $usages = [];
        $overrides = $this->loadingOverrides($block, $definition->resourceGroups);

        foreach ($definition->resourceGroups as $resourceGroup) {
            $loadingStrategy = $overrides[$resourceGroup]
                ?? $definition->resourceGroupLoadingStrategies[$resourceGroup]
                ?? $definition->defaultResourceLoadingStrategy;
            $defaultPresentationSettings = $definition->defaultPresentationSettings;
            $defaultPresentationSettings['loading_strategy'] = $loadingStrategy->value;

            $presentation = ResolvePresentationSettingsAction::make()->fromWidgetBlockData(
                $block,
                $defaultPresentationSettings,
            );

            if (isset($overrides[$resourceGroup])) {
                $instancePresentation = Arr::get($block, 'data.__capell.presentation');
                $instancePresentation = is_array($instancePresentation) ? $instancePresentation : [];
                $instancePresentation['loading_strategy'] = $overrides[$resourceGroup]->value;
                $presentation = ResolvePresentationSettingsAction::run(
                    $instancePresentation,
                    $defaultPresentationSettings,
                );
            }

            $usages[] = new FrontendWidgetResourceUsageData(
                widgetKey: $definition->key,
                resourceGroup: $resourceGroup,
                publicId: LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId($resourceGroup),
                presentation: $presentation,
                loadingStrategy: $presentation->loadingStrategy,
            );
        }

        return $usages;
    }

    /**
     * @param  array<string, mixed>  $block
     * @param  list<string>  $declaredGroups
     * @return array<string, PresentationLoadingStrategy>
     */
    private function loadingOverrides(array $block, array $declaredGroups): array
    {
        $overrides = Arr::get($block, 'data.__capell.resources.loading_overrides');
        if (! is_array($overrides)) {
            return [];
        }

        return collect($overrides)
            ->filter(fn (mixed $override): bool => is_array($override))
            ->mapWithKeys(function (array $override) use ($declaredGroups): array {
                $group = $override['group'] ?? null;
                $strategy = $override['loading_strategy'] ?? null;
                $loadingStrategy = is_string($strategy)
                    ? PresentationLoadingStrategy::tryFrom($strategy)
                    : null;

                if (! is_string($group)
                    || ! in_array($group, $declaredGroups, true)
                    || ! $loadingStrategy instanceof PresentationLoadingStrategy) {
                    return [];
                }

                return [$group => $loadingStrategy];
            })
            ->all();
    }
}
