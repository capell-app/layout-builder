<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Contracts\LayoutSidebarBlockContributor;
use Capell\LayoutBuilder\Data\LayoutSidebarBlockData;
use Capell\LayoutBuilder\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static void run(Layout $layout)
 */
class ApplyLayoutSidebarBlockContributionsAction
{
    use AsObject;

    public function handle(Layout $layout): void
    {
        $containers = $layout->getAttribute('containers');

        if (! is_array($containers)) {
            $containers = [];
        }

        if (! isset($containers['sidebar']) || ! is_array($containers['sidebar'])) {
            $containers['sidebar'] = $this->defaultSidebarContainer();
        }

        $sidebarBlocks = $containers['sidebar']['widgets'] ?? [];
        $sidebarBlocks = is_array($sidebarBlocks) ? $sidebarBlocks : [];

        $sidebarWidgetKeys = $this->widgetKeys($sidebarBlocks);

        foreach ($this->contributedBlocks($layout) as $sidebarBlock) {
            if (in_array($sidebarBlock->widgetKey, $sidebarWidgetKeys, true)) {
                continue;
            }

            if (! Widget::query()->where('key', $sidebarBlock->widgetKey)->exists()) {
                continue;
            }

            $sidebarBlocks[] = $sidebarBlock->toLayoutBlock();
            $sidebarWidgetKeys[] = $sidebarBlock->widgetKey;
        }

        $containers['sidebar']['widgets'] = $sidebarBlocks;

        $layout->update([
            'containers' => $containers,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSidebarContainer(): array
    {
        return [
            'meta' => [
                'colspan' => 3,
                'override_columns' => 1,
                'container' => 'full',
                'padding' => ['md'],
                'html_class' => 'sidebar-sticky space-y-8',
            ],
            'widgets' => [],
        ];
    }

    /**
     * @return array<int, LayoutSidebarBlockData>
     */
    private function contributedBlocks(Layout $layout): array
    {
        $layoutKey = (string) $layout->getAttribute('key');
        $blocks = [];

        foreach (app()->tagged(LayoutSidebarBlockContributor::TAG) as $contributor) {
            if (! $contributor instanceof LayoutSidebarBlockContributor) {
                continue;
            }

            foreach ($contributor->sidebarBlocks() as $sidebarBlock) {
                if (! $sidebarBlock->appliesTo($layoutKey)) {
                    continue;
                }

                $blocks[] = $sidebarBlock;
            }
        }

        return $blocks;
    }

    /**
     * @param  array<int, mixed>  $blocks
     * @return array<int, string>
     */
    private function widgetKeys(array $blocks): array
    {
        return collect($blocks)
            ->map(fn (mixed $block): ?string => is_array($block) ? ($block['widget_key'] ?? null) : null)
            ->filter(fn (?string $widgetKey): bool => is_string($widgetKey) && $widgetKey !== '')
            ->unique()
            ->values()
            ->all();
    }
}
