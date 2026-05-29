<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Actions;

use Capell\Core\Enums\ContainerWidthEnum;
use Capell\LayoutBuilder\Models\Widget;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * @method static ContainerWidthEnum run(Widget $block, ?string $default = null)
 */
class GetBlockContainerWidthAction
{
    use AsObject;

    private const string LAYOUT_CONTAINER_WIDTH_RESOLVER_SERVICE = 'capell.frontend.layout-container-width-resolver';

    public function handle(Widget $block, ?string $default = null): ContainerWidthEnum
    {
        $containerWidth = $block->getMeta('container');

        if ($containerWidth !== null) {
            return ContainerWidthEnum::from($containerWidth);
        }

        $resolvedWidth = $this->resolveFrontendContainerWidth($default);
        if ($resolvedWidth instanceof ContainerWidthEnum) {
            return $resolvedWidth;
        }

        return ContainerWidthEnum::tryFrom($default ?? '') ?? ContainerWidthEnum::Full;
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
