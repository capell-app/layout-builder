<?php

declare(strict_types=1);

use Capell\PublishingStudio\Filament\Resources\PublishingStudio\Pages\CompareVersionPage;
use Capell\PublishingStudio\Models\Workspace;
use Capell\Tests\Fixtures\Models\User;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('publishing-studio');

beforeEach(function (): void {
    Permission::findOrCreate('ViewAny:Workspace', 'web');
    Permission::findOrCreate('View:Workspace', 'web');

    $reviewer = User::factory()->create();
    $reviewer->givePermissionTo(['ViewAny:Workspace', 'View:Workspace']);

    test()->actingAs($reviewer);
});

it('mounts the compare page for an existing workspace and exposes the diff collection', function (): void {
    $workspace = Workspace::factory()->open()->create(['name' => 'Spring Refresh']);

    livewire(CompareVersionPage::class, ['record' => $workspace->getRouteKey()])
        ->assertSuccessful()
        ->assertSet('record.id', $workspace->id)
        ->assertSeeText('Spring Refresh');
});

it('aborts with 404 when the workspace does not exist', function (): void {
    expect(fn () => livewire(CompareVersionPage::class, ['record' => '999999']))
        ->toThrow(ModelNotFoundException::class);
});

it('classifies long / multi-line strings as long text', function (): void {
    $page = new CompareVersionPage;

    expect($page->isLongText('short'))->toBeFalse()
        ->and($page->isLongText(42))->toBeFalse()
        ->and($page->isLongText("line one\nline two"))->toBeTrue()
        ->and($page->isLongText(str_repeat('x', 200)))->toBeTrue();
});

it('renders a non-empty HTML diff for multi-line strings', function (): void {
    $page = new CompareVersionPage;

    $html = $page->renderHtmlDiff("alpha\nbeta", "alpha\ngamma");

    expect($html)->toBeString()->not->toBe('');
});
