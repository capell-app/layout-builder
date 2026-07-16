<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\Core\Models\Blueprint;
use Capell\LayoutBuilder\Models\Widget;
use Illuminate\Support\Arr;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static ContainerWidthEnum run(Widget $widget, ?string $default = null)
 */
class GetWidgetContainerWidthAction
{
    use AsFake;
    use AsObject;

    private const string LAYOUT_CONTAINER_WIDTH_RESOLVER_SERVICE = 'capell.frontend.layout-container-width-resolver';

    public function handle(Widget $widget, ?string $default = null): ContainerWidthEnum
    {
        $containerWidth = $this->widgetMeta($widget, 'container');

        if ($containerWidth !== null) {
            return ContainerWidthEnum::from($containerWidth);
        }

        $resolvedWidth = $this->resolveFrontendContainerWidth($default);
        if ($resolvedWidth instanceof ContainerWidthEnum) {
            return $resolvedWidth;
        }

        return ContainerWidthEnum::tryFrom($default ?? '') ?? ContainerWidthEnum::Full;
    }

    private function widgetMeta(Widget $widget, string $key, mixed $fallback = null): mixed
    {
        $meta = $widget->meta ?? [];

        if (is_array($meta) && Arr::has($meta, $key)) {
            $value = data_get($meta, $key);

            if (filled($value)) {
                return $value;
            }
        }

        $blueprint = $widget->relationLoaded('blueprint') ? $widget->getRelation('blueprint') : null;

        if (! $blueprint instanceof Blueprint && $widget->relationLoaded('blueprint')) {
            $blueprint = $widget->getRelation('blueprint');
        }

        if ($blueprint instanceof Blueprint) {
            $blueprintMeta = $blueprint->meta ?? [];

            if (is_array($blueprintMeta) && Arr::has($blueprintMeta, $key)) {
                $value = data_get($blueprintMeta, $key);

                if (filled($value)) {
                    return $value;
                }
            }
        }

        return $fallback;
    }

    private function resolveFrontendContainerWidth(?string $default): ?ContainerWidthEnum
    {
        if (! app()->bound(self::LAYOUT_CONTAINER_WIDTH_RESOLVER_SERVICE)) {
            return null;
        }

        $resolver = resolve(self::LAYOUT_CONTAINER_WIDTH_RESOLVER_SERVICE);

        if (is_callable($resolver)) {
            $resolvedWidth = $resolver($default);

            return $resolvedWidth instanceof ContainerWidthEnum ? $resolvedWidth : null;
        }

        if (is_object($resolver) && method_exists($resolver, 'resolve')) {
            $resolvedWidth = $resolver->resolve($default);

            return $resolvedWidth instanceof ContainerWidthEnum ? $resolvedWidth : null;
        }

        return null;
    }
}
