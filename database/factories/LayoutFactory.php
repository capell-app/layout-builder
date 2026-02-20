<?php

declare(strict_types=1);

namespace Capell\Layout\Database\Factories;

use Capell\Core\Models\Layout;
use Capell\Layout\Models\Widget;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Collection;

/**
 * @extends Factory<Layout>
 */
class LayoutFactory extends \Capell\Core\Database\Factories\LayoutFactory
{
    public function containers(): self
    {
        return $this->state([
            'containers' => function (): array {
                $firstWidget = Widget::query()->firstWhere('key', 'first')
                    ?: Widget::factory(['key' => 'first'])->create();

                $secondWidget = Widget::query()->firstWhere('key', 'second')
                    ?: Widget::factory(['key' => 'second'])->create();

                return [
                    'main' => [
                        'widgets' => [
                            [
                                'widget_key' => $firstWidget->key,
                                'occurrence' => 1,
                            ],
                            [
                                'widget_key' => $secondWidget->key,
                                'occurrence' => 1,
                            ],
                            [
                                'widget_key' => $firstWidget->key,
                                'occurrence' => 2,
                            ],
                        ],
                        'meta' => [],
                    ],
                ];
            },
        ]);
    }

    public function widgets(array|Collection $widgets): self
    {
        return $this->state([
            'containers' => function () use ($widgets): array {
                $widgetEntries = [];

                foreach ($widgets as $widget) {
                    $widgetEntries[] = [
                        'widget_key' => $widget instanceof $widget ? $widget->key : $widget,
                        'occurrence' => 1,
                    ];
                }

                return [
                    'main' => [
                        'widgets' => $widgetEntries,
                        'meta' => [],
                    ],
                ];
            },
        ]);
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $definition = parent::definition();

        return [
            ...$definition,
            'containers' => [],
        ];
    }
}
