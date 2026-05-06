<?php

declare(strict_types=1);

use Capell\PublishingStudio\Actions\GenerateWorkspacePreviewUrlAction;
use Capell\PublishingStudio\Http\Middleware\ResolveWorkspaceContext;
use Capell\PublishingStudio\Models\PreviewLink;
use Capell\PublishingStudio\Models\Workspace;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::get('/', fn (): string => 'ok')->name('capell-frontend.index');
    Route::get('{url}', fn (): string => 'ok')
        ->where('url', '.*')
        ->name('capell-frontend.page');
});

it('generates a workspace draft preview link for the iframe modal', function (): void {
    $workspace = Workspace::factory()->create();

    $url = (new GenerateWorkspacePreviewUrlAction)->handle($workspace);

    expect($url)
        ->toContain(ResolveWorkspaceContext::QUERY_PARAM . '=' . $workspace->uuid)
        ->toContain(ResolveWorkspaceContext::TOKEN_PARAM . '=');

    expect(PreviewLink::query()->where('workspace_id', $workspace->id)->exists())->toBeTrue();
});
