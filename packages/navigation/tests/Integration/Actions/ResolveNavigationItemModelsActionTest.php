<?php

declare(strict_types=1);

use Capell\Core\Contracts\Pageable;
use Capell\Core\Models\Page;
use Capell\Core\Models\Site;
use Capell\Navigation\Actions\ResolveNavigationItemModelsAction;
use Capell\Navigation\Enums\NavigationItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

it('resolves models for each morph type independently', function (): void {
    $site = Site::factory()->create();
    $page = Page::factory()->create(['site_id' => $site->getKey()]);

    $items = [
        [
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_type' => $page->getMorphClass(),
                'pageable_id' => $page->getKey(),
            ],
            'children' => [],
        ],
        [
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_type' => $site->getMorphClass(),
                'pageable_id' => $site->getKey(),
            ],
            'children' => [],
        ],
    ];

    $models = ResolveNavigationItemModelsAction::run($items);

    expect($models)->toHaveCount(2)
        ->and($models->contains(fn (Model $model): bool => $model instanceof Pageable && (int) $model->getKey() === (int) $page->getKey()))
        ->toBeTrue()
        ->and($models->contains(fn (Model $model): bool => $model instanceof Site && (int) $model->getKey() === (int) $site->getKey()))
        ->toBeTrue();
});

it('resolves morph aliases and nested child items', function (): void {
    Relation::morphMap([
        'page-alias' => Page::class,
    ]);

    $site = Site::factory()->create();
    $page = Page::factory()->create(['site_id' => $site->getKey()]);

    $items = [
        [
            'type' => NavigationItemType::Link->value,
            'data' => ['url' => 'https://example.com'],
            'children' => [
                [
                    'type' => NavigationItemType::Page->value,
                    'data' => [
                        'pageable_type' => 'page-alias',
                        'pageable_id' => $page->getKey(),
                    ],
                    'children' => [],
                ],
            ],
        ],
    ];

    $models = ResolveNavigationItemModelsAction::run($items);

    expect($models)->toHaveCount(1)
        ->and($models->first())->toBeInstanceOf(Page::class)
        ->and((int) $models->first()->getKey())->toBe((int) $page->getKey());
});

it('ignores invalid and unknown morph references', function (): void {
    $items = [
        [
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_type' => 'Unknown\\MissingModel',
                'pageable_id' => 1,
            ],
        ],
        [
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_type' => Page::class,
                'pageable_id' => null,
            ],
        ],
        [
            'type' => NavigationItemType::Page->value,
            'data' => [
                'pageable_type' => '',
                'pageable_id' => 123,
            ],
        ],
    ];

    $models = ResolveNavigationItemModelsAction::run($items);

    expect($models)->toBeEmpty();
});
