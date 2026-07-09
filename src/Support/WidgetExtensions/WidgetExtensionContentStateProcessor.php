<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\WidgetExtensions;

use Capell\Admin\Contracts\Widgets\ContentWidgetStateProcessor;
use Capell\LayoutBuilder\Contracts\WidgetExtensions\WidgetExtensionStateUpcaster;
use Illuminate\Contracts\Container\Container;

final class WidgetExtensionContentStateProcessor implements ContentWidgetStateProcessor
{
    public function __construct(
        private readonly WidgetExtensionRegistry $registry,
        private readonly Container $container,
    ) {}

    public function process(string $widgetKey, array $widget): array
    {
        $definition = $this->registry->definition($widgetKey);

        if ($definition === null) {
            return $widget;
        }

        $data = is_array($widget['data'] ?? null) ? $widget['data'] : [];
        $capellState = is_array($data['__capell'] ?? null) ? $data['__capell'] : [];
        $storedVersion = $capellState['state_version'] ?? 1;

        if (! is_int($storedVersion)
            || $storedVersion < 1
            || $storedVersion > $definition->stateVersion) {
            return $widget;
        }

        if ($storedVersion < $definition->stateVersion) {
            if ($definition->stateUpcaster === null) {
                return $widget;
            }

            $upcaster = $this->container->make($definition->stateUpcaster);

            if (! $upcaster instanceof WidgetExtensionStateUpcaster) {
                return $widget;
            }

            $data = $upcaster->upcast($data, $storedVersion, $definition->stateVersion);
        }

        $upcastedCapellState = is_array($data['__capell'] ?? null) ? $data['__capell'] : [];
        $instanceIdentity = $capellState['instance_id'] ?? null;

        if (is_string($instanceIdentity)) {
            $upcastedCapellState['instance_id'] = $instanceIdentity;
        }

        $upcastedCapellState['state_version'] = $definition->stateVersion;
        $data['__capell'] = $upcastedCapellState;
        $widget['data'] = $data;

        return $widget;
    }
}
