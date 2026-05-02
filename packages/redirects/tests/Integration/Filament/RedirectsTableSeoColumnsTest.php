<?php

declare(strict_types=1);

use Capell\Core\Database\Factories\LanguageFactory;
use Capell\Core\Database\Factories\SiteFactory;
use Capell\Core\Enums\RedirectStatusCodeEnum;
use Capell\Core\Enums\UrlTypeEnum;
use Capell\Core\Models\PageUrl;
use Capell\Redirects\Filament\Resources\Redirects\Tables\RedirectsTable;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;

function redirectTableConfigurationItems(string $methodName): array
{
    $reflection = new ReflectionClass(RedirectsTable::class);
    $method = $reflection->getMethod($methodName);

    return $method->invoke(null);
}

function resetRedirectTableHealthCache(): void
{
    $reflection = new ReflectionClass(RedirectsTable::class);
    $property = $reflection->getProperty('redirectHealthCache');

    $property->setValue(null, []);
}

function redirectTableHealthState(PageUrl $redirect): string
{
    $reflection = new ReflectionClass(RedirectsTable::class);
    $method = $reflection->getMethod('redirectHealthState');

    return $method->invoke(null, $redirect);
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

it('does not run redirect validation from table row rendering', function (): void {
    $reflection = new ReflectionClass(RedirectsTable::class);

    expect($reflection->hasMethod('chainWarningState'))->toBeFalse();
});

it('shows unknown redirect health when no snapshot exists', function (): void {
    resetRedirectTableHealthCache();

    $language = LanguageFactory::new()->create();
    $site = SiteFactory::new()->recycle($language)->language($language)->withTranslations($language)->create();
    $redirect = PageUrl::factory()
        ->site($site)
        ->language($language)
        ->state([
            'url' => '/missing-snapshot-source',
            'target_url' => '/missing-snapshot-target',
            'type' => UrlTypeEnum::Redirect,
            'status_code' => RedirectStatusCodeEnum::Permanent,
            'status' => true,
        ])
        ->create();

    $state = redirectTableHealthState($redirect);

    expect($state)
        ->toBe(__('redirects::table.chain_warning_unknown'))
        ->not->toBe(__('redirects::table.chain_warning_none'));
});
