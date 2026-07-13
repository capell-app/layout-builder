<?php

declare(strict_types=1);

use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Core\Models\Language;
use Capell\Core\Models\Layout as CoreLayout;
use Capell\Core\Models\Page;
use Capell\Frontend\Contracts\PublicLayoutGraphBuilder;
use Capell\LayoutBuilder\Health\LayoutBuilderHealthCheck;
use Illuminate\Support\Facades\Schema;

it('reports a compatible capell api version', function (): void {
    expect(LayoutBuilderHealthCheck::compatibleCapellApiVersion())->toBe('^1.0');
});

it('runs real diagnostics returning check results', function (): void {
    $results = LayoutBuilderHealthCheck::runDiagnostics();

    expect($results)->toHaveCount(3)
        ->and($results->every(static fn (mixed $result): bool => $result instanceof DoctorCheckResultData))->toBeTrue();
});

it('passes when tables, graph builder binding, and editor component are present', function (): void {
    $results = LayoutBuilderHealthCheck::runDiagnostics();

    expect(LayoutBuilderHealthCheck::passed())->toBeTrue()
        ->and($results->every(static fn (DoctorCheckResultData $result): bool => $result->passed))->toBeTrue();
});

it('fails the storage table check when a layout table is missing', function (): void {
    Schema::drop('layouts');

    $check = new LayoutBuilderHealthCheck;

    expect($check->missingTables())->toContain('layouts')
        ->and($check->storageTablesCheck()->passed)->toBeFalse()
        ->and(LayoutBuilderHealthCheck::passed())->toBeFalse();
});

it('fails the storage table check when preset or bulk change tables are missing', function (): void {
    Schema::drop('layout_presets');
    Schema::drop('layout_bulk_change_results');

    $check = new LayoutBuilderHealthCheck;

    expect($check->missingTables())->toContain('layout_presets', 'layout_bulk_change_results')
        ->and($check->storageTablesCheck()->passed)->toBeFalse()
        ->and($check->storageTablesCheck()->message)->toContain('layout_presets', 'layout_bulk_change_results');
});

it('fails the graph builder binding check when the contract resolves to a foreign implementation', function (): void {
    app()->forgetInstance(PublicLayoutGraphBuilder::class);
    app()->bind(PublicLayoutGraphBuilder::class, fn (): PublicLayoutGraphBuilder => new class implements PublicLayoutGraphBuilder
    {
        public function build(CoreLayout $layout, Page $page, Language $language): ?object
        {
            return null;
        }
    });

    $check = new LayoutBuilderHealthCheck;

    expect($check->publicLayoutGraphBuilderIsBound())->toBeFalse()
        ->and($check->publicLayoutGraphBuilderBindingCheck()->passed)->toBeFalse();
});

it('confirms the editor livewire component is registered', function (): void {
    $check = new LayoutBuilderHealthCheck;

    expect($check->editorComponentIsRegistered())->toBeTrue()
        ->and($check->editorComponentRegistrationCheck()->passed)->toBeTrue();
});
