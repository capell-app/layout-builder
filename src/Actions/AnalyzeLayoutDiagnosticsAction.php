<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutWidgetData;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class AnalyzeLayoutDiagnosticsAction
{
    use AsFake;
    use AsObject;

    /**
     * @return array<int, LayoutDiagnosticData>
     */
    /**
     * @param  array<int, string>|null  $knownWidgetKeys
     * @return array<array-key, mixed>
     */
    public function handle(LayoutBuilderStateData $state, ?array $knownWidgetKeys = null): array
    {
        $knownWidgetKeys ??= $this->knownWidgetKeys($state);

        $diagnostics = [];

        foreach ($state->containers as $containerKey => $container) {
            foreach (LayoutWidgetData::fromContainer($container) as $widgetIndex => $widget) {
                $widgetKey = LayoutWidgetData::key($widget);

                if (! is_string($widgetKey) || ! in_array($widgetKey, $knownWidgetKeys, true)) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Blocking,
                        code: 'unknown_widget',
                        message: __('capell-admin::message.unknown_widget', ['widget' => (string) $widgetKey]),
                        containerKey: (string) $containerKey,
                        widgetIndex: $widgetIndex,
                    );
                }
            }

            foreach (($container['meta']['responsive'] ?? []) as $breakpoint => $settings) {
                if (LayoutBreakpoint::tryFrom((string) $breakpoint) === null) {
                    continue;
                }

                $colspan = (int) ($settings['colspan'] ?? 12);
                if ($colspan < 1 || $colspan > 12) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Warning,
                        code: 'invalid_responsive_colspan',
                        message: __('capell-admin::message.invalid_responsive_colspan', ['container' => (string) $containerKey]),
                        containerKey: (string) $containerKey,
                        widgetIndex: null,
                    );
                }
            }
        }

        return $diagnostics;
    }

    /**
     * @return array<int, string>
     */
    private function knownWidgetKeys(LayoutBuilderStateData $state): array
    {
        $layoutWidgetKeys = collect($state->containers)
            ->flatMap(fn (array $container): array => LayoutWidgetData::fromContainer($container))
            ->map(static fn (array $widget): ?string => LayoutWidgetData::key($widget))
            ->filter(static fn (mixed $widgetKey): bool => is_string($widgetKey) && $widgetKey !== '')
            ->unique()
            ->values()
            ->all();

        return $layoutWidgetKeys === []
            ? []
            : Widget::query()
                ->whereIn('key', $layoutWidgetKeys)
                ->pluck('key')
                ->all();
    }
}
