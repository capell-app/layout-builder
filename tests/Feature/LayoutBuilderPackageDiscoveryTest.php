<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\PublicLayoutGraphBuilder;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutGraphBuilder;
use Illuminate\Support\Facades\Artisan;

it('discovers the layout builder package service provider', function (): void {
    expect(app()->getProvider(LayoutBuilderServiceProvider::class))->not->toBeNull();
});

it('registers the layout builder install command', function (): void {
    expect(array_keys(Artisan::all()))->toContain('capell:layout-builder-install');
});

it('registers the public layout graph builder for frontend rendering', function (): void {
    expect(resolve(PublicLayoutGraphBuilder::class))->toBeInstanceOf(LayoutBuilderPublicLayoutGraphBuilder::class);
});

it('registers page block assets as a cloneable relation when installed', function (): void {
    expect(CapellCore::getCloneableRelations('page'))->toContain('blockAssets');
});
