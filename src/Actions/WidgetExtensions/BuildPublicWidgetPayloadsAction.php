<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\WidgetExtensions;

use Capell\Frontend\Contracts\PublicContentWidgetPayloadBuilder;
use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionBatchPayloadResolver;
use Capell\LayoutBuilder\Data\WidgetExtensions\DiscoveredWidgetExtensionData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionDefinitionData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionPayloadBatchData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionPayloadInputData;
use Capell\LayoutBuilder\Data\WidgetExtensions\WidgetExtensionRenderContextData;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionInputFactory;
use Capell\LayoutBuilder\Support\WidgetExtensions\WidgetExtensionStateWalker;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Spatie\LaravelData\Data;
use Throwable;

final readonly class BuildPublicWidgetPayloadsAction implements PublicContentWidgetPayloadBuilder
{
    public function __construct(
        private WidgetExtensionStateWalker $walker,
        private WidgetExtensionInputFactory $inputFactory,
        private Container $container,
    ) {}

    /** @return array<string, object> */
    public function build(FrontendRenderContextData $context): array
    {
        /** @var array<string, array{definition: WidgetExtensionDefinitionData, items: list<WidgetExtensionPayloadInputData>}> $groups */
        $groups = [];

        foreach ($this->walker->fromContext($context) as $discovered) {
            $input = $this->validatedInput($discovered);
            if (! $input instanceof Data) {
                continue;
            }

            $key = $discovered->definition->key;
            $groups[$key] ??= ['definition' => $discovered->definition, 'items' => []];
            $groups[$key]['items'][] = new WidgetExtensionPayloadInputData($discovered->instanceId, $input);
        }

        $payloads = [];
        $renderContext = WidgetExtensionRenderContextData::fromFrontendContext($context);

        foreach ($groups as $group) {
            $this->resolveGroup($group['definition'], $group['items'], $renderContext, $payloads);
        }

        return $payloads;
    }

    private function validatedInput(DiscoveredWidgetExtensionData $discovered): ?Data
    {
        try {
            return $this->inputFactory->make($discovered);
        } catch (Throwable $throwable) {
            $this->diagnostic('Widget extension input was quarantined.', $discovered->definition->key, $throwable);

            return null;
        }
    }

    /**
     * @param  list<WidgetExtensionPayloadInputData>  $items
     * @param  array<string, object>  $payloads
     */
    private function resolveGroup(
        WidgetExtensionDefinitionData $definition,
        array $items,
        WidgetExtensionRenderContextData $context,
        array &$payloads,
    ): void {
        try {
            $resolved = $definition->batchPayloadResolver === null
                ? $this->convertInputs($definition, $items)
                : $this->resolveBatch($definition, new WidgetExtensionPayloadBatchData($items, $context));

            foreach ($items as $item) {
                $result = $resolved[$item->instanceId] ?? null;
                $renderClass = $definition->renderData;

                if (! $result instanceof $renderClass) {
                    $this->diagnostic('Widget extension payload result was quarantined.', $definition->key);

                    continue;
                }

                $payloads[$item->instanceId] = $result;
            }
        } catch (Throwable $throwable) {
            $this->diagnostic('Widget extension payload batch failed.', $definition->key, $throwable);
        }
    }

    /** @return array<string, Data> */
    private function resolveBatch(WidgetExtensionDefinitionData $definition, WidgetExtensionPayloadBatchData $batch): array
    {
        $resolverClass = $definition->batchPayloadResolver;
        if ($resolverClass === null) {
            throw new RuntimeException('Missing widget extension payload resolver.');
        }

        $resolver = $this->container->make($resolverClass);
        if (! $resolver instanceof WidgetExtensionBatchPayloadResolver) {
            throw new RuntimeException('Invalid widget extension payload resolver.');
        }

        return $resolver->resolve($batch);
    }

    /** @param list<WidgetExtensionPayloadInputData> $items
     * @return array<string, Data>
     */
    private function convertInputs(WidgetExtensionDefinitionData $definition, array $items): array
    {
        $payloads = [];
        $renderClass = $definition->renderData;

        foreach ($items as $item) {
            $payloads[$item->instanceId] = $renderClass::from($item->input->toArray());
        }

        return $payloads;
    }

    private function diagnostic(string $message, string $widgetKey, ?Throwable $throwable = null): void
    {
        try {
            Log::warning($message, array_filter([
                'widget_key' => $widgetKey,
                'failure_type' => $throwable === null ? null : $throwable::class,
            ]));
        } catch (Throwable) {
            // Diagnostics cannot make a public page unavailable.
        }
    }
}
