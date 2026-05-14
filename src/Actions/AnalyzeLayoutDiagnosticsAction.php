<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Widget;
use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Lorisleiva\Actions\Concerns\AsAction;

final class AnalyzeLayoutDiagnosticsAction
{
    use AsAction;

    /**
     * @return array<int, LayoutDiagnosticData>
     */
    public function handle(LayoutBuilderStateData $state): array
    {
        $layoutWidgetKeys = collect($state->containers)
            ->flatMap(fn (array $container): array => array_map(
                static fn (array $widget): mixed => $widget['widget_key'] ?? null,
                $container['widgets'] ?? [],
            ))
            ->filter(static fn (mixed $widgetKey): bool => is_string($widgetKey) && $widgetKey !== '')
            ->unique()
            ->values()
            ->all();

        $knownWidgetKeys = $layoutWidgetKeys === []
            ? []
            : Widget::query()
                ->whereIn('key', $layoutWidgetKeys)
                ->pluck('key')
                ->all();

        $diagnostics = [];

        foreach ($state->containers as $containerKey => $container) {
            foreach (($container['widgets'] ?? []) as $widgetIndex => $widget) {
                $widgetKey = $widget['widget_key'] ?? null;

                if (! is_string($widgetKey) || ! in_array($widgetKey, $knownWidgetKeys, true)) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Blocking,
                        code: 'unknown_widget',
                        message: __('capell-admin::message.unknown_widget', ['widget' => (string) $widgetKey]),
                        containerKey: (string) $containerKey,
                        widgetIndex: (int) $widgetIndex,
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
}
