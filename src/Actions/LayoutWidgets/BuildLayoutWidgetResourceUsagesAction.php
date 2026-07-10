<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\LayoutWidgets;

use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\LayoutBuilder\Data\Assets\LayoutWidgetResourceUsageData;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionStateWalker;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsObject;

class BuildLayoutWidgetResourceUsagesAction
{
    use AsObject;

    public function __construct(
        private readonly WidgetExtensionStateWalker $extensionWalker,
        private readonly WidgetExtensionRegistry $extensionRegistry,
    ) {}

    /**
     * @param  array<int|string, mixed>  $content
     * @return array<int, LayoutWidgetResourceUsageData>
     */
    public function handle(array $content, LayoutWidgetTarget $target): array
    {
        $usages = $target === LayoutWidgetTarget::FrontendBlade
            ? $this->canonicalExtensionUsages($content)
            : [];

        foreach (array_values($content) as $index => $block) {
            if (! is_array($block)) {
                continue;
            }

            if (! is_string($block['type'] ?? null)) {
                continue;
            }

            if ($this->extensionRegistry->definition($block['type']) !== null) {
                continue;
            }

            $definition = resolve(LayoutWidgetRegistry::class)->definition($block['type'], $target);
            if ($definition === null) {
                continue;
            }

            if ($definition->resourceGroups === []) {
                continue;
            }

            foreach ($definition->resourceGroups as $resourceGroup) {
                $loadingStrategy = $definition->resourceGroupLoadingStrategies[$resourceGroup]
                    ?? $definition->defaultLoadingStrategy;
                $defaultPresentationSettings = $definition->defaultPresentationSettings;

                if ($loadingStrategy instanceof PresentationLoadingStrategy) {
                    $defaultPresentationSettings['loading_strategy'] = $loadingStrategy->value;
                }

                $usages[] = new LayoutWidgetResourceUsageData(
                    widgetKey: $definition->key,
                    resourceGroup: $resourceGroup,
                    publicId: LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId($resourceGroup),
                    presentation: ResolvePresentationSettingsAction::make()->fromWidgetBlockData(
                        $block,
                        $defaultPresentationSettings,
                    ),
                    loadingStrategy: $loadingStrategy,
                );
            }
        }

        return $usages;
    }

    /**
     * @param  array<int|string, mixed>  $content
     * @return list<LayoutWidgetResourceUsageData>
     */
    private function canonicalExtensionUsages(array $content): array
    {
        $usages = [];

        foreach ($this->extensionWalker->walk($content) as $discovered) {
            $definition = $discovered->definition;
            $block = $discovered->widget;
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
                    $presentation = ResolvePresentationSettingsAction::make()->handle(
                        $instancePresentation,
                        $defaultPresentationSettings,
                    );
                }

                $usages[] = new LayoutWidgetResourceUsageData(
                    widgetKey: $definition->key,
                    resourceGroup: $resourceGroup,
                    publicId: LayoutBuilderLayoutWidgetResourceUsageContributor::resourceGroupPublicId($resourceGroup),
                    presentation: $presentation,
                    loadingStrategy: $presentation->loadingStrategy,
                );
            }
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
