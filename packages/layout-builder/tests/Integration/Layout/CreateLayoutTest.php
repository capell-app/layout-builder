<?php

declare(strict_types=1);

use Capell\Core\Enums\LayoutEnum;
use Capell\Core\Support\Creator\LayoutCreator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('creates the default layout with correct containers and widgets', function (): void {
    $layout = (new LayoutCreator)->create(LayoutEnum::Default);
    $layout->refresh();

    expect($layout->key)->toBe('default');
    expect($layout->containers)->toBeArray();
    expect($layout->containers)->toHaveKey('main');
    expect($layout->containers)->toHaveKey('sidebar');

    $mainWidgets = $layout->containers['main']['widgets'] ?? null;
    $sidebarWidgets = $layout->containers['sidebar']['widgets'] ?? null;

    expect($mainWidgets)->toBeArray();
    expect($sidebarWidgets)->toBeArray();
    expect(collect($mainWidgets)->pluck('widget_key')->all())
        ->toBe(['breadcrumbs', 'page-content', 'children']);
    expect(collect($sidebarWidgets)->pluck('widget_key')->all())
        ->toBe(['latest-pages']);
});

it('creates the results layout with correct containers and widgets', function (): void {
    $layout = (new LayoutCreator)->create(LayoutEnum::Results);
    $layout->refresh();

    expect($layout->key)->toBe('results');
    expect($layout->containers)->toBeArray();
    expect($layout->containers)->toHaveKey('main');
    expect($layout->containers)->toHaveKey('sidebar');

    $mainWidgets = $layout->containers['main']['widgets'] ?? null;
    $sidebarWidgets = $layout->containers['sidebar']['widgets'] ?? null;

    expect($mainWidgets)->toBeArray();
    expect($sidebarWidgets)->toBeArray();
    expect(collect($mainWidgets)->pluck('widget_key')->all())
        ->toBe(['breadcrumbs', 'page-content', 'page-slot']);
    expect(collect($sidebarWidgets)->pluck('widget_key')->all())
        ->toBe(['latest-pages']);
});

it('creates the home layout with correct containers and widgets', function (): void {
    $layout = (new LayoutCreator)->create(LayoutEnum::Home);
    $layout->refresh();

    expect($layout->key)->toBe('home');
    expect($layout->containers)->toBeArray();
    expect($layout->containers)->toHaveKey('main');

    $mainWidgets = $layout->containers['main']['widgets'] ?? null;
    expect($mainWidgets)->toBeArray();
    expect(collect($mainWidgets)->pluck('widget_key')->all())
        ->toBe(['page-content']);
});

it('can create core layouts before layout-builder layout columns exist', function (): void {
    Schema::table('layouts', function (Blueprint $table): void {
        $table->dropColumn(['containers', 'widgets']);
    });

    $layout = (new LayoutCreator)->create(LayoutEnum::Default);

    expect($layout->key)->toBe('default');
});
