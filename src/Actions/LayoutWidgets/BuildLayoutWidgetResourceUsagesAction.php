<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\LayoutWidgets;

use Capell\Core\Actions\Presentation\ResolvePresentationSettingsAction;
use Capell\Core\Enums\PresentationLoadingStrategy;
use Capell\Frontend\Data\Assets\FrontendWidgetResourceUsageData;
use Capell\LayoutBuilder\Actions\WidgetExtensions\BuildWidgetExtensionResourceUsagesAction;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Enums\LayoutWidgetTarget;
use Capell\LayoutBuilder\Support\LayoutBuilderLayoutWidgetResourceUsageContributor;
use Capell\LayoutBuilder\Support\LayoutWidgets\LayoutWidgetRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionRegistry;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionStateWalker;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static list<FrontendWidgetResourceUsageData> run(array<int|string, mixed> $content, LayoutWidgetTarget $target)
 */
class BuildLayoutWidgetResourceUsagesAction
{
    use AsFake;
    use AsObject;

    public function __construct(
        private readonly WidgetExtensionStateWalker $extensionWalker,
        private readonly WidgetExtensionRegistry $extensionRegistry,
    ) {}

    /**
     * @param  array<int|string, mixed>  $content
     * @return list<FrontendWidgetResourceUsageData>
     */
    public function handle(array $content, LayoutWidgetTarget $target): array
    {
        $usages = $target === LayoutWidgetTarget::FrontendBlade
            ? $this->canonicalExtensionUsages($content)
            : [];

        foreach (array_values($content) as $block) {
            if (! is_array($block)) {
                continue;
            }

            if (! is_string($block['type'] ?? null)) {
                continue;
            }

            if ($this->extensionRegistry->definition($block['type']) instanceof WidgetExtensionDefinitionData) {
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

                $usages[] = new FrontendWidgetResourceUsageData(
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
     * @return list<FrontendWidgetResourceUsageData>
     */
    private function canonicalExtensionUsages(array $content): array
    {
        $usages = [];

        foreach ($this->extensionWalker->walk($content) as $discovered) {
            array_push($usages, ...BuildWidgetExtensionResourceUsagesAction::run(
                $discovered->definition,
                $discovered->widget,
            ));
        }

        return $usages;
    }
}
