<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Support\Sitemap;

use Capell\Core\Contracts\ModelInterceptors\TypeInterceptorInterface;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;

/**
 * Defines the canonical "sitemap" page type owned by Site Discovery.
 *
 * The Type::key value is registered from Site Discovery rather than core
 * because the page type is meaningful only when Site Discovery is installed.
 */
final class SitemapPageType
{
    public const Key = 'sitemap';

    public const ComponentView = 'capell-site-discovery.page.sitemap';

    public static function createType(): Type
    {
        $defaults = [
            'key' => self::Key,
            'type' => TypeEnum::Page,
            'name' => __('capell-site-discovery::generic.sitemap'),
            'meta' => [
                'listable' => false,
                'component' => self::ComponentView,
            ],
        ];

        /** @var class-string<Type> $typeModel */
        $typeModel = Type::class;

        return CapellCore::createOrUpdateModel(
            $typeModel,
            ['key' => self::Key, 'type' => TypeEnum::Page],
            fn (array $data): array => CapellCore::mergeModelInterceptorData($defaults, $data),
            TypeInterceptorInterface::class,
        );
    }
}
