<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Models\Layout;
use Capell\Core\Support\Creator\LayoutCreator;

it('seeds the default layout with a full width page content container', function (): void {
    $layout = resolve(LayoutCreator::class)->createDefaultLayout();

    expect($layout->refresh()->containers)->toBe([
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
    ]);
});

it('does not overwrite an existing default layout container structure', function (): void {
    $layout = Layout::factory()->create([
        'key' => LayoutEnum::Default->value,
        'containers' => [
            'hero' => [
                'widgets' => [
                    [
                        'widget_key' => 'hero',
                        'occurrence' => 1,
                    ],
                ],
                'meta' => [
                    'colspan' => 6,
                ],
            ],
        ],
    ]);

    resolve(LayoutCreator::class)->createDefaultLayout();

    expect($layout->refresh()->containers)->toBe([
        'hero' => [
            'widgets' => [
                [
                    'widget_key' => 'hero',
                    'occurrence' => 1,
                ],
            ],
            'meta' => [
                'colspan' => 6,
            ],
        ],
    ]);
});
