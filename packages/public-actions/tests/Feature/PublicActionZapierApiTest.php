<?php

declare(strict_types=1);

use Capell\Core\Models\Site;
use Capell\PublicActions\Actions\CreatePublicActionIntegrationTokenAction;
use Capell\PublicActions\Enums\PublicActionIntegrationTokenAbility;
use Capell\PublicActions\Models\PublicAction;
use Capell\PublicActions\Models\PublicActionSubmission;
use Illuminate\Support\Facades\Date;

it('authenticates Zapier API tokens and lists exposed actions', function (): void {
    $created = CreatePublicActionIntegrationTokenAction::run('Zapier');

    PublicAction::factory()->create([
        'key' => 'zapier-action',
        'name' => 'Zapier action',
        'handler_key' => 'test.handler',
        'settings' => ['zapier_enabled' => true],
    ]);

    $this
        ->withToken($created->plainTextToken)
        ->getJson('/api/public-actions/zapier/actions')
        ->assertOk()
        ->assertJsonPath('actions.0.key', 'zapier-action');
});

it('rejects revoked or missing Zapier API tokens', function (): void {
    $this->getJson('/api/public-actions/zapier/me')->assertUnauthorized();

    $created = CreatePublicActionIntegrationTokenAction::run('Zapier');
    $created->token->forceFill(['revoked_at' => now()])->save();

    $this
        ->withToken($created->plainTextToken)
        ->getJson('/api/public-actions/zapier/me')
        ->assertUnauthorized();
});

it('submits public actions from Zapier and exposes sanitized submissions', function (): void {
    $created = CreatePublicActionIntegrationTokenAction::run('Zapier');

    PublicAction::factory()->create([
        'key' => 'submit-from-zapier',
        'handler_key' => 'test.handler',
        'settings' => ['zapier_enabled' => true],
    ]);

    $this
        ->withToken($created->plainTextToken)
        ->postJson('/api/public-actions/zapier/actions/submit-from-zapier/submissions', [
            'email' => 'person@example.test',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this
        ->withToken($created->plainTextToken)
        ->getJson('/api/public-actions/zapier/submissions')
        ->assertOk()
        ->assertJsonPath('submissions.0.action_key', 'submit-from-zapier')
        ->assertJsonPath('submissions.0.payload.email', 'person@example.test')
        ->assertJsonMissingPath('submissions.0.metadata.ip');
});

it('only submits actions exposed to Zapier', function (): void {
    $created = CreatePublicActionIntegrationTokenAction::run('Zapier');

    PublicAction::factory()->create([
        'key' => 'hidden-from-zapier',
        'handler_key' => 'test.handler',
        'settings' => [],
    ]);

    $this
        ->withToken($created->plainTextToken)
        ->postJson('/api/public-actions/zapier/actions/hidden-from-zapier/submissions', [
            'email' => 'person@example.test',
        ])
        ->assertNotFound();

    expect(PublicActionSubmission::query()->count())->toBe(0);
});

it('lists Zapier submissions after the requested cursor', function (): void {
    $created = CreatePublicActionIntegrationTokenAction::run('Zapier');
    $action = PublicAction::factory()->create([
        'key' => 'cursor-action',
        'handler_key' => 'test.handler',
        'settings' => ['zapier_enabled' => true],
    ]);

    $first = PublicActionSubmission::factory()->for($action, 'action')->create([
        'submitted_at' => Date::parse('2026-05-09 10:00:00'),
    ]);
    $second = PublicActionSubmission::factory()->for($action, 'action')->create([
        'submitted_at' => Date::parse('2026-05-09 10:01:00'),
    ]);
    $third = PublicActionSubmission::factory()->for($action, 'action')->create([
        'submitted_at' => Date::parse('2026-05-09 10:02:00'),
    ]);

    $this
        ->withToken($created->plainTextToken)
        ->getJson('/api/public-actions/zapier/submissions?after_id=' . $second->getKey())
        ->assertOk()
        ->assertJsonCount(1, 'submissions')
        ->assertJsonPath('submissions.0.id', (string) $third->getKey())
        ->assertJsonPath('next_after_id', (string) $third->getKey());

    expect($first->getKey())->toBeLessThan($second->getKey());
});

it('only lists submissions for actions exposed to Zapier', function (): void {
    $created = CreatePublicActionIntegrationTokenAction::run('Zapier');
    $visibleAction = PublicAction::factory()->create([
        'key' => 'visible-submission-action',
        'handler_key' => 'test.handler',
        'settings' => ['zapier_enabled' => true],
    ]);
    $hiddenAction = PublicAction::factory()->create([
        'key' => 'hidden-submission-action',
        'handler_key' => 'test.handler',
        'settings' => [],
    ]);

    PublicActionSubmission::factory()->for($hiddenAction, 'action')->create();
    PublicActionSubmission::factory()->for($visibleAction, 'action')->create([
        'payload' => ['email' => 'visible@example.test'],
    ]);

    $this
        ->withToken($created->plainTextToken)
        ->getJson('/api/public-actions/zapier/submissions')
        ->assertOk()
        ->assertJsonCount(1, 'submissions')
        ->assertJsonPath('submissions.0.action_key', 'visible-submission-action');
});

it('keeps site-scoped Zapier tokens inside their assigned site and global actions', function (): void {
    $assignedSite = Site::factory()->create();
    $otherSite = Site::factory()->create();
    $created = CreatePublicActionIntegrationTokenAction::run('Site token', siteId: $assignedSite->id);

    PublicAction::factory()->create([
        'key' => 'assigned-site-action',
        'site_id' => $assignedSite->id,
        'site_scope_key' => 'site:' . $assignedSite->id,
        'handler_key' => 'test.handler',
        'settings' => ['zapier_enabled' => true],
    ]);
    PublicAction::factory()->create([
        'key' => 'other-site-action',
        'site_id' => $otherSite->id,
        'site_scope_key' => 'site:' . $otherSite->id,
        'handler_key' => 'test.handler',
        'settings' => ['zapier_enabled' => true],
    ]);

    $this
        ->withToken($created->plainTextToken)
        ->getJson('/api/public-actions/zapier/actions')
        ->assertOk()
        ->assertJsonPath('actions.0.key', 'assigned-site-action')
        ->assertJsonMissingPath('actions.1');

    $this
        ->withToken($created->plainTextToken)
        ->postJson('/api/public-actions/zapier/actions/other-site-action/submissions', [
            'email' => 'person@example.test',
        ])
        ->assertNotFound();
});

it('enforces Zapier token abilities', function (): void {
    $created = CreatePublicActionIntegrationTokenAction::run(
        name: 'Read only',
        abilities: [PublicActionIntegrationTokenAbility::ReadSubmissions],
    );

    PublicActionSubmission::factory()->create();

    $this
        ->withToken($created->plainTextToken)
        ->getJson('/api/public-actions/zapier/actions')
        ->assertForbidden();

    $this
        ->withToken($created->plainTextToken)
        ->getJson('/api/public-actions/zapier/submissions')
        ->assertOk();
});
