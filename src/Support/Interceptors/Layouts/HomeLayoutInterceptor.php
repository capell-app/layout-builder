<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Interceptors\Layouts;

use Capell\Core\Contracts\ModelInterceptors\LayoutInterceptorInterface;
use Capell\Core\Models\Layout;
use Capell\Core\Support\Database\RuntimeSchemaState;
use Capell\Core\Support\TableName;

final class HomeLayoutInterceptor implements LayoutInterceptorInterface
{
    public function beforeCreate(array $data): array
    {
        return $data;
    }

    public function afterCreated(Layout $layout, array $data): void
    {
        if (! resolve(RuntimeSchemaState::class)->hasColumn(TableName::resolve('layouts'), 'containers')) {
            return;
        }

        $layout->update([
            'containers' => [],
        ]);
    }
}
