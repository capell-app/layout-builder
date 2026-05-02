<?php

declare(strict_types=1);

use Capell\Redirects\Filament\Resources\Redirects\Tables\RedirectsTable;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;

function redirectTableConfigurationItems(string $methodName): array
{
    $reflection = new ReflectionClass(RedirectsTable::class);
    $method = $reflection->getMethod($methodName);

    return $method->invoke(null);
}

it('exposes redirect seo columns in the table configuration', function (): void {
    $columns = collect(redirectTableConfigurationItems('getTableColumns'));

    expect($columns->map(fn (Column $column): string => $column->getName())->all())
        ->toContain('chain_warning')
        ->toContain('last_hit_at');

    $lastHitColumn = $columns->first(fn (Column $column): bool => $column->getName() === 'last_hit_at');

    expect($lastHitColumn)->toBeInstanceOf(Column::class)
        ->and($lastHitColumn->isToggledHiddenByDefault())->toBeFalse();
});

it('exposes the hit count bucket filter in the table configuration', function (): void {
    $filters = collect(redirectTableConfigurationItems('getTableFilters'));

    expect($filters->map(fn (BaseFilter $filter): string => $filter->getName())->all())
        ->toContain('hit_count_bucket');
});
