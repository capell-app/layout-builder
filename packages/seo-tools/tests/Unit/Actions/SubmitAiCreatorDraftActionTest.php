<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\SubmitAiCreatorDraftAction;
use Capell\SeoTools\Models\AiCreatorSession;
use Capell\SeoTools\Support\ContentTargetResolver;
use Illuminate\Auth\Access\AuthorizationException;

function createReviewAiCreatorSession(array $state = []): AiCreatorSession
{
    return AiCreatorSession::query()->create([
        'site_id' => 1,
        'user_id' => 10,
        'status' => 'review',
        'stage' => 3,
        'intent' => 'Build a page',
        'layout_proposal' => [['section_type' => 'hero', 'fields' => []]],
        ...$state,
    ]);
}

it('rejects AI creator draft submission for a different user', function (): void {
    $session = createReviewAiCreatorSession();
    $action = new SubmitAiCreatorDraftAction(new ContentTargetResolver);

    expect(fn (): null => $action->handle($session, userId: 11, siteId: 1))
        ->toThrow(AuthorizationException::class);
});

it('rejects AI creator draft submission for a different site', function (): void {
    $session = createReviewAiCreatorSession();
    $action = new SubmitAiCreatorDraftAction(new ContentTargetResolver);

    expect(fn (): null => $action->handle($session, userId: 10, siteId: 2))
        ->toThrow(AuthorizationException::class);
});

it('rejects AI creator draft submission outside review status', function (): void {
    $session = createReviewAiCreatorSession(['status' => 'generating']);
    $action = new SubmitAiCreatorDraftAction(new ContentTargetResolver);

    expect(fn (): null => $action->handle($session, userId: 10, siteId: 1))
        ->toThrow(AuthorizationException::class);
});
