<?php

declare(strict_types=1);

use Capell\Core\Models\Page;
use Capell\Tests\Fixtures\Models\User;
use Capell\Workspaces\Actions\CreatePageDraftWorkspaceAction;
use Capell\Workspaces\Enums\WorkspaceKindEnum;
use Capell\Workspaces\Enums\WorkspaceStatusEnum;
use Capell\Workspaces\Models\Workspace;

it('creates a new workspace with SinglePageDraft kind and Open status', function (): void {
    $page = Page::factory()->create(['name' => 'About Us']);
    $user = User::factory()->create();

    $workspace = CreatePageDraftWorkspaceAction::run($page, $user);

    expect($workspace)->toBeInstanceOf(Workspace::class)
        ->and($workspace->kind)->toBe(WorkspaceKindEnum::SinglePageDraft)
        ->and($workspace->status)->toBe(WorkspaceStatusEnum::Open)
        ->and($workspace->created_by)->toBe($user->getKey())
        ->and($workspace->name)->toContain('About Us');
});

it('sets the workspace name with the page name and a timestamp', function (): void {
    $page = Page::factory()->create(['name' => 'Contact']);
    $user = User::factory()->create();

    $workspace = CreatePageDraftWorkspaceAction::run($page, $user);

    expect($workspace->name)->toMatch('/^Draft: Contact · \d{4}-\d{2}-\d{2} \d{2}:\d{2}$/');
});

it('generates a unique slug for each draft workspace', function (): void {
    $page = Page::factory()->create(['name' => 'Home']);
    $user = User::factory()->create();

    $first = CreatePageDraftWorkspaceAction::run($page, $user);
    $second = CreatePageDraftWorkspaceAction::run($page, $user);

    expect($first->slug)->not->toBe($second->slug);
});
