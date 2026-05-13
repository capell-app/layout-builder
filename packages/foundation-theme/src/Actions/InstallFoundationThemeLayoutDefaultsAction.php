<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Actions;

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Enums\LayoutEnum;
use Capell\Core\LayoutBuilder\Support\Creator\WidgetCreator;
use Capell\Core\Models\Layout;
use Capell\Core\Support\Creator\LayoutCreator;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static array{created: int, updated: int, skipped: int} run(bool $force = false)
 */
final class InstallFoundationThemeLayoutDefaultsAction
{
    use AsObject;

    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function handle(bool $force = false): array
    {
        $layoutCreator = resolve(LayoutCreator::class);
        $layoutCreator->createHomeLayout();
        $layoutCreator->createDefaultLayout();

        $widgetCreator = resolve(WidgetCreator::class);
        $widgetCreator->breadcrumbWidget();
        $widgetCreator->childrenWidget();
        $widgetCreator->latestPagesWidget();
        $widgetCreator->pageContentWidget();
        $widgetCreator->siblingsWidget();

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($this->layoutDefaults() as $layoutKey => $containers) {
            $layout = $this->resolveLayout($layoutKey);
            $hadContainers = $layout->containers !== [];

            if ($hadContainers && ! $force && ! $this->hasLegacyHomeHeroDefault($layout)) {
                $result['skipped']++;

                continue;
            }

            $layout->update([
                'containers' => $containers,
                'widgets' => $this->widgetKeys($containers),
            ]);

            $result[$hadContainers ? 'updated' : 'created']++;
        }

        return $result;
    }

    private function resolveLayout(string $layoutKey): Layout
    {
        return Layout::query()->where('key', $layoutKey)->firstOrFail();
    }

    private function hasLegacyHomeHeroDefault(Layout $layout): bool
    {
        return $layout->key === LayoutEnum::Home->value
            && $layout->containers === [
                'hero' => [
                    'widgets' => [
                        ['widget_key' => 'hero'],
                    ],
                ],
            ]
            && $layout->widgets === ['hero'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function layoutDefaults(): array
    {
        return [
            LayoutEnum::Home->value => [
                'main' => $this->mainContainer([
                    ['widget_key' => 'page-content'],
                ], 12),
            ],
            LayoutEnum::Default->value => [
                'main' => $this->mainContainer([
                    ['widget_key' => 'breadcrumbs'],
                    ['widget_key' => 'page-content'],
                    ['widget_key' => 'children'],
                ]),
                'sidebar' => $this->sidebarContainer([
                    ['widget_key' => 'siblings'],
                    ['widget_key' => 'latest-pages'],
                ]),
            ],
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $widgets
     * @return array<string, mixed>
     */
    private function sidebarContainer(array $widgets): array
    {
        return [
            'meta' => [
                'colspan' => 3,
                'override_columns' => 1,
                'container' => ContainerWidthEnum::Full,
                'tag' => 'aside',
                'padding' => ['md'],
                'html_class' => 'sidebar-sticky space-y-8',
            ],
            'widgets' => $widgets,
        ];
    }

    /**
     * @param  array<int, array<string, string>>  $widgets
     * @return array<string, mixed>
     */
    private function mainContainer(array $widgets, int $colspan = 9): array
    {
        return [
            'meta' => [
                'colspan' => $colspan,
            ],
            'widgets' => $widgets,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @return array<int, string>
     */
    private function widgetKeys(array $containers): array
    {
        return collect($containers)
            ->flatMap(fn (array $container): array => $container['widgets'] ?? [])
            ->unique('widget_key')
            ->pluck('widget_key')
            ->values()
            ->all();
    }
}
