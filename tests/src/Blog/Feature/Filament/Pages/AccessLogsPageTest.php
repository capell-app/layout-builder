<?php

declare(strict_types=1);

use Capell\Admin\Filament\Pages\AccessLogsPage;
use Capell\Blog\Models\Article;
use Capell\Core\Models\AccessLog;
use Capell\Tests\Support\Concerns\CreatesAdminUser;

use function Pest\Livewire\livewire;

use Spatie\Permission\Models\Permission;

uses(CreatesAdminUser::class)
    ->group('access-logs');

test('can render articles in access logs', function (): void {
    Permission::create(['name' => 'View:AccessLogsPage', 'guard_name' => 'web']);
    test()->actingAsAdmin();
    auth()->user()->givePermissionTo('View:AccessLogsPage');

    $article = Article::factory()->create();

    AccessLog::factory()->create();

    AccessLog::factory()->page($article)->create();

    livewire(AccessLogsPage::class)
        ->assertSuccessful()
        ->assertCountTableRecords(2);
});
