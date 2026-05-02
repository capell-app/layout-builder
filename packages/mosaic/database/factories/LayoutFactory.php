<?php

declare(strict_types=1);

namespace Capell\Mosaic\Database\Factories;

use Capell\Core\Models\Layout;
use Capell\Mosaic\Models\Widget;
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
                $firstWidget = Widget::query()->firstWhere('key', 'first');
                if (! $firstWidget instanceof Widget) {
                    $firstWidget = Widget::factory(['key' => 'first'])->create();
                }

                $secondWidget = Widget::query()->firstWhere('key', 'second');
                if (! $secondWidget instanceof Widget) {
                    $secondWidget = Widget::factory(['key' => 'second'])->create();
                }

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
                        'widget_key' => $widget instanceof Widget ? $widget->key : $widget,
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
            'widgets' => fn (): array => collect($widgets)
                ->map(fn (Widget|string $widget): string => $widget instanceof Widget ? $widget->key : $widget)
                ->unique()
                ->values()
                ->all(),
        ])->afterCreating(function (Layout $layout) use ($widgets): void {
            $widgetKeys = collect($widgets)
                ->map(fn (Widget|string $widget): string => $widget instanceof Widget ? $widget->key : $widget)
                ->unique()
                ->values()
                ->all();

            $layout->forceFill(['widgets' => $widgetKeys])->save();
        });
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
