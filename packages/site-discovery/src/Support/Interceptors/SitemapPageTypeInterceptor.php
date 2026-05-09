<?php

declare(strict_types=1);

namespace Capell\SiteDiscovery\Support\Interceptors;

use Capell\Admin\Filament\Configurators\Types\PageTypeConfigurator;
use Capell\Core\Contracts\ModelInterceptors\TypeInterceptorInterface;
use Capell\Core\Models\Type;

class SitemapPageTypeInterceptor implements TypeInterceptorInterface
{
    public function beforeCreate(array $data): array
    {
        $data['admin'] = [
            'type_configurator' => PageTypeConfigurator::getKey(),
            'icon' => 'heroicon-o-map',
            'required_fields' => ['title'],
        ];

        return $data;
    }

    public function afterCreated(Type $type, array $data): void {}
}
