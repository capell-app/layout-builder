<?php

declare(strict_types=1);

use Capell\Navigation\Data\NavigationItemData;
use Capell\Navigation\Enums\NavigationItemType;
use Capell\Navigation\Models\Navigation;
use Pest\Expectation;
use Pest\Expectations\HigherOrderExpectation;
use Spatie\LaravelData\DataCollection;

it('casts stored navigation items to NavigationItemsData', function (): void {
    $payload = [
        'a63f834a-b938-4eb1-8cc1-7fd7bceec35d' => [
            'label' => 'Home',
            'type' => 'page',
            'data' => [
                'site_id' => 1,
                'pageable_id' => 1,
                'pageable_type' => 'page',
                'hidden_label' => true,
                'icon' => 'heroicon-o-home',
            ],
            'children' => [
                'b1c8e5d2-9c3e-4f0a-9c8a-1f2b3c4d5e6f' => [
                    'label' => 'About',
                    'type' => 'page',
                    'data' => [
                        'site_id' => 1,
                        'pageable_id' => 2,
                        'pageable_type' => 'page',
                    ],
                    'children' => [],
                ],
            ],
        ],
        '656093f6-d215-4cbf-82cf-b14fb886ac8a' => [
            'label' => null,
            'type' => 'page',
            'data' => [
                'site_id' => 1,
                'pageable_id' => 4,
                'pageable_type' => 'page',
            ],
            'children' => [],
        ],
    ];

    $navigation = Navigation::factory()->state(['items' => $payload])->create();

    expect($navigation->items)
        ->toBeInstanceOf(DataCollection::class)
        ->toHaveCount(2)
        ->toHaveKeys([
            'a63f834a-b938-4eb1-8cc1-7fd7bceec35d',
            '656093f6-d215-4cbf-82cf-b14fb886ac8a',
        ])
        ->first()->scoped(
            fn (Expectation $item): HigherOrderExpectation => $item->toBeInstanceOf(NavigationItemData::class)
                ->label->toBe('Home')
                ->type->toBeInstanceOf(NavigationItemType::class)
                ->type->toBe(NavigationItemType::Page)
                ->children->toBeInstanceOf(DataCollection::class)
                ->children->first()->scoped(
                    fn (Expectation $item): HigherOrderExpectation => $item->toBeInstanceOf(NavigationItemData::class)
                        ->label->toBe('About')
                        ->type->toBeInstanceOf(NavigationItemType::class)
                        ->type->toBe(NavigationItemType::Page)
                        ->children->toBeInstanceOf(DataCollection::class),
                ),
        );
});

it('casts an empty items array to an empty NavigationItemsData object', function (): void {
    $navigation = Navigation::factory()->create([
        'items' => [],
    ])->refresh();

    expect($navigation->items)->toBeInstanceOf(DataCollection::class)
        ->toBeEmpty();
});
