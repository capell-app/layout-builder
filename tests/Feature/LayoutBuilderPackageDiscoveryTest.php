<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\PublicLayoutGraphBuilder;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutGraphBuilder;
use Capell\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;

it('discovers the layout builder package service provider', function (): void {
    expect(app()->getProvider(LayoutBuilderServiceProvider::class))->not->toBeNull();
});

it('registers the layout builder install command', function (): void {
    expect(array_keys(Artisan::all()))->toContain('capell:layout-builder-install');
});

it('registers the public layout graph builder for frontend rendering', function (): void {
    expect(resolve(PublicLayoutGraphBuilder::class))->toBeInstanceOf(LayoutBuilderPublicLayoutGraphBuilder::class);
});

it('registers page widget assets as a cloneable relation when installed', function (): void {
    expect(CapellCore::getCloneableRelations('page'))->toContain('widgetAssets');
});

it('contributes the permission aware layout builder welcome tour chapter', function (): void {
    Gate::before(fn (): bool => true);
    test()->actingAs(User::factory()->create());

    $step = collect(CapellAdmin::getWelcomeTourSteps())->firstWhere('key', 'capell-layout-builder.widgets');

    expect($step)->not->toBeNull()
        ->and(data_get($step, 'chapter'))->toBe('layout-builder')
        ->and(data_get($step, 'route'))->toBe('/admin/layout-builder/widgets');
});
