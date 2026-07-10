<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetExtensions;

use Capell\Frontend\Data\FrontendRenderContextData;
use Capell\LayoutBuilder\Data\WidgetExtensions\DiscoveredWidgetExtensionData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

final readonly class WidgetExtensionStateWalker
{
    /** @var list<string> */
    private const array WIDGET_COLLECTION_KEYS = ['widgets', 'blocks', 'children', 'items', 'elements'];

    /** @var list<string> */
    private const array WIDGET_TARGET_KEYS = ['target_widget'];

    private const int MAX_DEPTH = 16;

    private const int MAX_NODES = 2000;

    public function __construct(
        private WidgetExtensionRegistry $registry,
    ) {}

    /** @return list<DiscoveredWidgetExtensionData> */
    public function fromContext(FrontendRenderContextData $context): array
    {
        $sources = [];
        $page = $context->page;
        $translation = $page instanceof Model && $page->relationLoaded('translation')
            ? $page->getRelation('translation')
            : null;
        $content = $translation instanceof Model ? $translation->getAttribute('content') : null;

        if (is_array($content)) {
            $sources[] = $content;
        }

        $containers = $context->layout?->getAttribute('containers');
        if (is_array($containers)) {
            $sources[] = $containers;
        }

        return $this->walk($sources);
    }

    /** @param array<mixed> $sources
     * @return list<DiscoveredWidgetExtensionData>
     */
    public function walk(array $sources): array
    {
        $found = [];
        $identities = [];
        $visited = 0;
        $visit = function (mixed $value, int $depth, bool $widgetPosition) use (&$visit, &$found, &$identities, &$visited): void {
            if (! is_array($value) || $depth > self::MAX_DEPTH || ++$visited > self::MAX_NODES) {
                return;
            }

            $type = $value['type'] ?? null;
            $data = $value['data'] ?? null;
            $definition = is_string($type) ? $this->registry->definition($type) : null;

            // Unknown widgets are immutable opaque state. Never inspect their
            // payload for canonical-looking nested blocks.
            if ($widgetPosition && is_string($type) && is_array($data) && $definition === null) {
                return;
            }

            if ($widgetPosition && $definition !== null && is_array($data)) {
                $capell = is_array($data['__capell'] ?? null) ? $data['__capell'] : [];
                $instanceId = $capell['instance_id'] ?? null;

                if (is_string($instanceId) && $instanceId !== '') {
                    if (! isset($identities[$instanceId])) {
                        $identities[$instanceId] = true;
                        $found[] = new DiscoveredWidgetExtensionData($instanceId, $definition, $value);
                    } else {
                        $this->diagnostic('Duplicate widget extension identity was quarantined.', $type);
                    }
                } else {
                    $this->diagnostic('Widget extension without a valid identity was quarantined.', $type);
                }
            }

            foreach ($value as $key => $child) {
                if (! is_array($child)) {
                    continue;
                }

                if (is_string($key) && in_array($key, self::WIDGET_TARGET_KEYS, true)) {
                    $visit($child, $depth + 1, true);

                    continue;
                }

                if (is_string($key) && in_array($key, self::WIDGET_COLLECTION_KEYS, true)) {
                    foreach ($child as $widget) {
                        $visit($widget, $depth + 1, true);
                    }

                    continue;
                }

                $visit($child, $depth + 1, false);
            }
        };

        foreach ($sources as $source) {
            if (! is_array($source)) {
                continue;
            }

            if ($this->isWidgetEnvelope($source)) {
                $visit($source, 0, true);

                continue;
            }

            if (array_is_list($source)) {
                foreach ($source as $widget) {
                    $visit($widget, 0, true);
                }

                continue;
            }

            $visit($source, 0, false);
        }

        return $found;
    }

    /** @param array<mixed> $value */
    private function isWidgetEnvelope(array $value): bool
    {
        return is_string($value['type'] ?? null) && is_array($value['data'] ?? null);
    }

    private function diagnostic(string $message, string $widgetKey): void
    {
        try {
            Log::warning($message, ['widget_key' => $widgetKey]);
        } catch (Throwable) {
            // A logger failure must not affect public rendering.
        }
    }
}
