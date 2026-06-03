<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Translation;
use Capell\LayoutBuilder\Data\AdminLayoutPreviewData;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

final class RenderAdminLayoutPreviewAction
{
    use AsObject;

    /**
     * @param  array<string, array<string, mixed>>  $containers
     * @param  array<string, array<int, Widget>>  $containerWidgets
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @param  array<string, mixed>  $pageFormState
     */
    public function handle(
        array $containers,
        array $containerWidgets,
        array $assets,
        ?Pageable $page,
        array $pageFormState = [],
    ): AdminLayoutPreviewData {
        $previewPage = $this->previewPage($page, $pageFormState);
        $nodeMap = [];

        $html = resolve(Factory::class)->make('capell-layout-builder::filament.layout-builder.admin-preview.page', [
            'containers' => $containers,
            'containerWidgets' => $containerWidgets,
            'assets' => $assets,
            'page' => $previewPage,
            'nodeMap' => &$nodeMap,
            'handleForContainer' => fn (string $containerKey): string => $this->handleForContainer($containerKey, $nodeMap),
            'handleForWidget' => fn (string $containerKey, int $widgetIndex): string => $this->handleForWidget($containerKey, $widgetIndex, $nodeMap),
            'renderWidgetPreview' => fn (Widget $widget, array $containerWidget, string $containerKey, int $widgetIndex): HtmlString => $this->renderWidgetPreview(
                widget: $widget,
                containerWidget: $containerWidget,
                containerKey: $containerKey,
                widgetIndex: $widgetIndex,
                assets: $assets,
                page: $previewPage,
            ),
        ])->render();

        return new AdminLayoutPreviewData(
            html: $html,
            signature: hash('sha256', json_encode([
                'containers' => $containers,
                'assets' => $this->assetSignature($assets),
                'page' => $pageFormState,
            ], JSON_THROW_ON_ERROR)),
            nodeMap: $nodeMap,
        );
    }

    /**
     * @param  array<string, array{type: string, containerKey: string, widgetIndex?: int}>  $nodeMap
     */
    private function handleForContainer(string $containerKey, array &$nodeMap): string
    {
        $handle = hash('xxh128', 'container:' . $containerKey);
        $nodeMap[$handle] = [
            'type' => 'container',
            'containerKey' => $containerKey,
        ];

        return $handle;
    }

    /**
     * @param  array<string, array{type: string, containerKey: string, widgetIndex?: int}>  $nodeMap
     */
    private function handleForWidget(string $containerKey, int $widgetIndex, array &$nodeMap): string
    {
        $handle = hash('xxh128', 'widget:' . $containerKey . ':' . $widgetIndex);
        $nodeMap[$handle] = [
            'type' => 'widget',
            'containerKey' => $containerKey,
            'widgetIndex' => $widgetIndex,
        ];

        return $handle;
    }

    /**
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @param  array<string, mixed>  $containerWidget
     */
    private function renderWidgetPreview(
        Widget $widget,
        array $containerWidget,
        string $containerKey,
        int $widgetIndex,
        array $assets,
        ?Pageable $page,
    ): HtmlString {
        $previewData = ResolveAdminWidgetPreviewDataAction::run(
            widget: $widget,
            containerWidget: $containerWidget,
            page: $page,
            assetCount: count($assets[$containerKey][$widgetIndex] ?? []),
            hasPageAssets: $this->hasPageAssets($assets[$containerKey][$widgetIndex] ?? []),
        );

        $view = $this->previewView($previewData->view);

        try {
            return new HtmlString(resolve(Factory::class)->make($view, [
                'previewData' => $previewData,
                'widget' => $widget,
                'containerWidget' => $containerWidget,
                'containerKey' => $containerKey,
                'widgetIndex' => $widgetIndex,
            ])->render());
        } catch (Throwable $throwable) {
            report($throwable);

            return new HtmlString(resolve(Factory::class)->make('capell-layout-builder::filament.layout-builder.admin-preview.widget-fallback', [
                'previewData' => $previewData,
            ])->render());
        }
    }

    private function previewView(string $view): string
    {
        if (Str::of($view)->contains('::filament.layout-builder.previews.')) {
            return $view;
        }

        return 'capell-layout-builder::filament.layout-builder.previews.default';
    }

    /**
     * @param  array<string, array<int, array<int, array<string, mixed>>>>  $assets
     * @return array<string, mixed>
     */
    private function assetSignature(array $assets): array
    {
        return collect($assets)
            ->map(fn (array $containerAssets): array => collect($containerAssets)
                ->map(fn (array $widgetAssets): array => collect($widgetAssets)
                    ->map(fn (array $asset): array => Arr::only($asset, [
                        'id',
                        'asset_id',
                        'asset_type',
                        'order',
                        'occurrence',
                        'pageable_id',
                        'pageable_type',
                    ]))
                    ->values()
                    ->all())
                ->all())
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $assets
     */
    private function hasPageAssets(array $assets): bool
    {
        foreach ($assets as $asset) {
            if (isset($asset['pageable_type'], $asset['pageable_id'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $pageFormState
     */
    private function previewPage(?Pageable $page, array $pageFormState): ?Pageable
    {
        if (! $page instanceof Model) {
            return $page;
        }

        $previewPage = clone $page;
        $previewPage->exists = true;
        $previewPage->forceFill(Arr::only($pageFormState, [
            'admin',
            'layout_id',
            'meta',
            'name',
            'order',
            'parent_id',
            'site_id',
            'blueprint_id',
        ]));

        if ($page->relationLoaded('translation')) {
            $previewPage->setRelation('translation', $this->previewTranslation($page, $pageFormState));
        }

        return $previewPage;
    }

    /**
     * @param  array<string, mixed>  $pageFormState
     */
    private function previewTranslation(Model $page, array $pageFormState): ?Translation
    {
        $translation = $page->getRelation('translation');

        if (! $translation instanceof Translation) {
            return null;
        }

        $previewTranslation = clone $translation;
        $stateTranslations = is_array($pageFormState['translations'] ?? null) ? $pageFormState['translations'] : [];

        foreach ($stateTranslations as $stateTranslation) {
            if (! is_array($stateTranslation)) {
                continue;
            }

            if ((int) ($stateTranslation['language_id'] ?? 0) !== (int) $translation->language_id) {
                continue;
            }

            $previewTranslation->forceFill(Arr::only($stateTranslation, ['content', 'language_id', 'meta', 'title']));

            return $previewTranslation;
        }

        return $previewTranslation;
    }
}
