<?php

declare(strict_types=1);

namespace Capell\Layout\Services\Creator;

use Capell\Admin\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use InvalidArgumentException;

class LayoutUpdater
{
    public function setup(?string $key = null): void
    {
        if ($key === null) {
            $this->defaultLayout(Layout::firstWhere('default', true));
            $this->homeLayout(Layout::firstWhere('key', \Capell\Layout\Enums\LayoutEnum::Home));
            $this->resultsLayout(Layout::firstWhere('key', LayoutEnum::Results));
            $this->tagsLayout(Layout::firstWhere('key', LayoutEnum::Tags));

            return;
        }

        match ($key) {
            \Capell\Layout\Enums\LayoutEnum::Home->value => $this->homeLayout(Layout::firstWhere('key', \Capell\Layout\Enums\LayoutEnum::Home)),
            LayoutEnum::Results->value => $this->resultsLayout(Layout::firstWhere('key', LayoutEnum::Results)),
            LayoutEnum::Tags->value => $this->tagsLayout(Layout::firstWhere('key', LayoutEnum::Tags)),
            LayoutEnum::Default->value => $this->defaultLayout(Layout::firstWhere('default', true)),
            default => throw new InvalidArgumentException('Invalid layout key: '.$key)
        };
    }

    public function defaultLayout(Layout $layout): void
    {
        $layout->update([
            'containers' => [
                'main' => [
                    'meta' => [
                        'colspan' => 9,
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
                        'html_class' => 'sidebar-sticky space-y-10 pt-10 pb-20',
                    ],
                    'widgets' => [
                        ['widget_key' => 'latest-pages'],
                    ],
                ],
            ],
        ]);
    }

    public function homeLayout(Layout $layout): void
    {
        $layout->update([
            'containers' => [
                'main' => [
                    'widgets' => [
                        ['widget_key' => 'page-content'],
                    ],
                ],
            ],
        ]);
    }

    public function resultsLayout(Layout $layout): void
    {
        $layout->update([
            'containers' => [
                'main' => [
                    'meta' => [
                        'colspan' => 9,
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
                        'html_class' => 'sidebar-sticky space-y-10 pt-10 pb-20',
                    ],
                    'widgets' => [
                        ['widget_key' => 'latest-pages'],
                    ],
                ],
            ],
        ]);
    }

    public function tagsLayout(Layout $layout): void
    {
        $layout->update([
            'containers' => [
                'main' => [
                    'meta' => [
                        'colspan' => 9,
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
                        'html_class' => 'sidebar-sticky space-y-10 pt-10 pb-20',
                    ],
                    'widgets' => [
                        ['widget_key' => 'latest-pages'],
                    ],
                ],
            ],
        ]);
    }
}
