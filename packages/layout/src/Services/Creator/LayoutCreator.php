<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Core\Enums\ModelEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\Layout;

class LayoutCreator
{
    /**
     * @var class-string<Layout>
     */
    private readonly string $layoutModel;

    public function __construct()
    {
        $this->layoutModel = CapellCore::getModel(ModelEnum::Layout);
    }

    public function create(string $key): Layout
    {
        return match ($key) {
            'home' => $this->homeLayout(),
            'results' => $this->resultsLayout(),
            'tags' => $this->tagsLayout(),
            'default' => $this->defaultLayout(),
        };
    }

    public function defaultLayout(): Layout
    {
        return $this->layoutModel::firstOrCreate(['default' => true], [
            'key' => 'default',
            'name' => __('capell-admin::generic.default'),
            'group' => 'default',
            'containers' => [
                'main' => [
                    'meta' => [
                        'colspan' => 9,
                        'container' => 'full',
                    ],
                    'widgets' => [
                        ['widget_key' => 'breadcrumbs'],
                        ['widget_key' => 'page-content'],
                        ['widget_key' => 'children'],
                    ],
                ],
                'sidebar' => [
                    'meta' => [
                        'colspan' => 3,
                        'override_columns' => 1,
                        'container' => 'full',
                        'padding' => ['md'],
                    ],
                    'widgets' => [
                        ['widget_key' => 'latest-pages'],
                    ],
                ],
            ],
        ]);
    }

    public function homeLayout(): Layout
    {
        return $this->layoutModel::firstOrCreate(['key' => 'home'], [
            'name' => __('capell-admin::generic.home'),
            'group' => 'default',
            'containers' => [
                'main' => [
                    'widgets' => [
                        ['widget_key' => 'page-content'],
                    ],
                ],
            ],
        ]);
    }

    public function resultsLayout(): Layout
    {
        return $this->layoutModel::firstOrCreate(['key' => 'results'], [
            'name' => __('capell-admin::generic.results_page'),
            'group' => 'system',
            'containers' => [
                'main' => [
                    'meta' => [
                        'colspan' => 9,
                        'container' => 'full',
                    ],
                    'widgets' => [
                        ['widget_key' => 'breadcrumbs'],
                        ['widget_key' => 'page-content'],
                        ['widget_key' => 'page-slot'],
                    ],
                ],
                'sidebar' => [
                    'meta' => [
                        'colspan' => 3,
                        'override_columns' => 1,
                        'container' => 'full',
                        'padding' => ['md'],
                    ],
                    'widgets' => [
                        ['widget_key' => 'latest-pages'],
                    ],
                ],
            ],
        ]);
    }

    public function tagsLayout(): Layout
    {
        return $this->layoutModel::firstOrCreate(['key' => 'tags'], [
            'name' => __('capell-admin::generic.tags_page'),
            'group' => 'system',
            'containers' => [
                'main' => [
                    'meta' => [
                        'colspan' => 9,
                        'container' => 'full',
                    ],
                    'widgets' => [
                        ['widget_key' => 'breadcrumbs'],
                        ['widget_key' => 'tags', 'meta' => ['hide_content' => true]],
                    ],
                ],
                'sidebar' => [
                    'meta' => [
                        'colspan' => 3,
                        'override_columns' => 1,
                        'container' => 'full',
                        'padding' => ['md'],
                    ],
                    'widgets' => [
                        ['widget_key' => 'latest-pages'],
                    ],
                ],
            ],
        ]);
    }
}
