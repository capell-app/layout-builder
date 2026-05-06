<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\Sitemap;

use Capell\Core\Contracts\ModelInterceptors\TypeInterceptorInterface;
use Capell\Core\Enums\TypeEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Type;

/**
 * Defines the canonical "sitemap" page type owned by seo-suite.
 *
 * The Type::key value is registered from seo-suite rather than core because
 * the page type is meaningful only when seo-suite is installed.
 */
final class SitemapPageType
{
    public const Key = 'sitemap';

    public const ComponentView = 'capell-seo-suite.page.sitemap';

    public static function createType(): Type
    {
        $defaults = [
            'key' => self::Key,
            'type' => TypeEnum::Page,
            'name' => __('capell::generic.sitemap'),
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
