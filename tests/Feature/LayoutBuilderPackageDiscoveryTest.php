<?php

declare(strict_types=1);

use Capell\Core\Facades\CapellCore;
use Capell\Frontend\Contracts\PublicLayoutGraphBuilder;
use Capell\LayoutBuilder\Filament\Resources\Widgets\WidgetResource;
use Capell\LayoutBuilder\LayoutBuilderServiceProvider;
use Capell\LayoutBuilder\Support\LayoutBuilderPublicLayoutGraphBuilder;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
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

it('registers page widget assets as a cloneable relation when installed', function (): void {
    expect(CapellCore::getCloneableRelations('page'))->toContain('widgetAssets');
});

it('exposes a stable widgets target for an application-owned welcome tour manifest', function (): void {
    $livewire = Mockery::mock(HasTable::class);
    $livewire->shouldIgnoreMissing();
    $livewire->shouldReceive('makeFilamentTranslatableContentDriver')->andReturn(null)->byDefault();
    $livewire->shouldReceive('getTableFilterState')->andReturn([])->byDefault();
    $livewire->shouldReceive('isTableLoaded')->andReturnTrue()->byDefault();
    $livewire->shouldReceive('getTableArguments')->andReturn([])->byDefault();

    $table = WidgetResource::table(Table::make($livewire));

    expect($table->getExtraAttributes())->toMatchArray([
        'data-tour-id' => 'welcome-tour-layout-builder-widgets',
    ]);
});
