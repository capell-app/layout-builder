<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Interceptors\Layouts;

use Capell\Core\Contracts\ModelInterceptors\LayoutInterceptorInterface;
use Capell\Core\Models\Layout;
use Capell\Core\Support\Database\RuntimeSchemaState;

final class DefaultLayoutInterceptor implements LayoutInterceptorInterface
{
    public function beforeCreate(array $data): array
    {
        return $data;
    }

    public function afterCreated(Layout $layout, array $data): void
    {
        if (! app(RuntimeSchemaState::class)->hasColumn('layouts', 'containers')) {
            return;
        }

        $layout->update([
            'containers' => [],
            'widgets' => [],
        ]);
    }
}
