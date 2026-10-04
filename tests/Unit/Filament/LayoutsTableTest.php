<?php

declare(strict_types=1);

use Capell\Core\Models\Layout;
use Capell\LayoutBuilder\Filament\Resources\Layouts\Tables\LayoutsTable;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

uses(CreatesAdminUser::class);

it('describes an empty layout table with translated filter-safe copy', function (): void {
    $table = configureLayoutBuilderLayoutsTable();

    expect(__('capell-layout-builder::table.layouts_empty_heading'))->toBe('No layouts found')
        ->and(__('capell-layout-builder::table.layouts_empty_description'))
        ->toBe('No layouts are available for the current selection. Try adjusting your search or filters.')
        ->and($table->getEmptyStateHeading())->toBe('No layouts found')
        ->and($table->getEmptyStateDescription())
        ->toBe('No layouts are available for the current selection. Try adjusting your search or filters.');
});

it('keeps the layout empty state truthful when a real filter excludes an existing layout', function (): void {
    test()->actingAsAdmin();

    $layout = Layout::factory()->create([
        'name' => 'Filtered Layout',
        'status' => true,
    ]);
    $table = configureLayoutBuilderLayoutsTable();
    $disabledFilter = $table->getFilter('disabled');
    $query = $table->getQuery();

    if (! $disabledFilter instanceof Filter) {
        throw new RuntimeException('Expected the layouts table to expose a disabled filter.');
    }

    if (! $query instanceof Builder) {
        throw new RuntimeException('Expected the layouts table to expose an Eloquent query.');
    }

    $filteredQuery = $disabledFilter->apply(clone $query, ['isActive' => true]);

    expect($query->whereKey($layout)->exists())->toBeTrue()
        ->and($filteredQuery->count())->toBe(0)
        ->and($filteredQuery->whereKey($layout)->exists())->toBeFalse()
        ->and($table->getEmptyStateDescription())
        ->toBe('No layouts are available for the current selection. Try adjusting your search or filters.');
});

function configureLayoutBuilderLayoutsTable(): Table
{
    $livewire = Mockery::mock(HasTable::class);
    $table = Table::make($livewire)->query(Layout::query());

    $livewire->shouldReceive('makeFilamentTranslatableContentDriver')->andReturn(null)->byDefault();
    $livewire->shouldReceive('getTable')->andReturn($table)->byDefault();

    return LayoutsTable::configure($table);
}
