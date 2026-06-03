<?php

declare(strict_types=1);

namespace Capell\LayoutBuilder\Support\Interceptors\Layouts;

use Capell\Core\Contracts\ModelInterceptors\LayoutInterceptorInterface;
use Capell\Core\Models\Layout;
use Capell\Core\Support\Database\RuntimeSchemaState;

final class DefaultLayoutInterceptor implements LayoutInterceptorInterface
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function beforeCreate(array $data): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function afterCreated(Layout $layout, array $data): void
    {
        $this->ensureStarterContainers($layout);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function afterCreatedOrUpdated(Layout $layout, array $data): void
    {
        $this->ensureStarterContainers($layout);
    }

    private function ensureStarterContainers(Layout $layout): void
    {
        if (! resolve(RuntimeSchemaState::class)->hasColumn('layouts', 'containers')) {
            return;
        }

        if (is_array($layout->containers) && $layout->containers !== []) {
            return;
        }

        $layout->update([
            'containers' => [
                'main' => [
                    'widgets' => [
                        [
                            'widget_key' => 'page-content',
                            'occurrence' => 1,
                        ],
                    ],
                    'meta' => [
                        'colspan' => 12,
                    ],
                ],
            ],
        ]);
    }
}
