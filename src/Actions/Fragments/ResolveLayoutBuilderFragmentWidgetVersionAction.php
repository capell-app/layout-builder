<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions\Fragments;

use Capell\Core\Models\Language;
use Capell\Core\Models\Page;
use Capell\LayoutBuilder\Actions\ResolvePublicWidgetAssetsAction;
use Capell\LayoutBuilder\Models\Widget;
use Capell\LayoutBuilder\Models\WidgetAsset;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveLayoutBuilderFragmentWidgetVersionAction
{
    use AsFake;
    use AsObject;

    public function handle(
        Widget $widget,
        Page $page,
        Language $language,
        string $containerKey,
        int $occurrence,
    ): string {
        $freshWidget = Widget::query()
            ->withTrashed()
            ->whereKey($widget->getKey())
            ->firstOrFail();
        $freshWidget->load(['translation' => fn ($query) => $query->where('language_id', $language->getKey())]);

        $assets = ResolvePublicWidgetAssetsAction::make()
            ->attachedAssets($freshWidget, $page, $language, $containerKey, $occurrence);

        $payload = $this->canonicalize([
            'widget' => $freshWidget->getAttributes(),
            'translation' => $freshWidget->getRelationValue('translation') instanceof Model
                ? $freshWidget->getRelationValue('translation')->getAttributes()
                : null,
            'assets' => $assets
                ->map(fn (WidgetAsset $widgetAsset): array => [
                    'pivot' => $widgetAsset->getAttributes(),
                    'asset' => $widgetAsset->asset instanceof Model
                        ? $widgetAsset->asset->getAttributes()
                        : null,
                    'translation' => $widgetAsset->asset instanceof Model
                        && $widgetAsset->asset->getRelationValue('translation') instanceof Model
                            ? $widgetAsset->asset->getRelationValue('translation')->getAttributes()
                            : null,
                ])
                ->all(),
        ]);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function canonicalize(array $values): array
    {
        if (! array_is_list($values)) {
            ksort($values);
        }

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $values[$key] = $this->canonicalize($value);
            }
        }

        return $values;
    }
}
