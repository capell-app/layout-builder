<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\LayoutBuilder\Data\LayoutBuilderStateData;
use Capell\LayoutBuilder\Data\LayoutDiagnosticData;
use Capell\LayoutBuilder\Enums\LayoutBreakpoint;
use Capell\LayoutBuilder\Enums\LayoutDiagnosticSeverity;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Support\LayoutBlockData;
use Lorisleiva\Actions\Concerns\AsAction;

final class AnalyzeLayoutDiagnosticsAction
{
    use AsAction;

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
            foreach (LayoutBlockData::fromContainer($container) as $blockIndex => $block) {
                $widgetKey = LayoutBlockData::key($block);

                if (! is_string($widgetKey) || ! in_array($widgetKey, $knownWidgetKeys, true)) {
                    $diagnostics[] = new LayoutDiagnosticData(
                        severity: LayoutDiagnosticSeverity::Blocking,
                        code: 'unknown_block',
                        message: __('capell-admin::message.unknown_block', ['block' => (string) $widgetKey]),
                        containerKey: (string) $containerKey,
                        blockIndex: $blockIndex,
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
                        blockIndex: null,
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
            ->flatMap(fn (array $container): array => LayoutBlockData::fromContainer($container))
            ->map(static fn (array $block): ?string => LayoutBlockData::key($block))
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
