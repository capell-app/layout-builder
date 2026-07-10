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
        $visit = function (mixed $value, int $depth) use (&$visit, &$found, &$identities, &$visited): void {
            if (! is_array($value) || $depth > self::MAX_DEPTH || ++$visited > self::MAX_NODES) {
                return;
            }

            $type = $value['type'] ?? null;
            $data = $value['data'] ?? null;
            $definition = is_string($type) ? $this->registry->definition($type) : null;

            if ($definition !== null && is_array($data)) {
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

            foreach ($value as $child) {
                if (is_array($child)) {
                    $visit($child, $depth + 1);
                }
            }
        };

        $visit($sources, 0);

        return $found;
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
