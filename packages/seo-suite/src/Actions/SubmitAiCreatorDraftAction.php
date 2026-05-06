<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Actions;

use Capell\SeoSuite\Contracts\ContentTargetContract;
use Capell\SeoSuite\Models\AiCreatorSession;
use Capell\SeoSuite\Support\ContentTargetResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Lorisleiva\Actions\Concerns\AsAction;

class SubmitAiCreatorDraftAction
{
    use AsAction;

    public function __construct(
        private readonly ContentTargetResolver $targetResolver,
    ) {}

    public function handle(AiCreatorSession $session, ?int $userId = null, ?int $siteId = null): void
    {
        $this->authorizeSession($session, $userId, $siteId);

        $sections = $session->layout_proposal ?? [];

        $target = $this->targetResolver->preferred();

        if ($target instanceof ContentTargetContract) {
            $target->apply($sections, $session);
        }

        $session->update(['status' => 'submitted']);
    }

    private function authorizeSession(AiCreatorSession $session, ?int $userId, ?int $siteId): void
    {
        throw_unless($session->status === 'review', AuthorizationException::class, 'AI creator session is not ready for submission.');

        if ($userId !== null) {
            throw_unless($session->user_id === $userId, AuthorizationException::class, 'AI creator session does not belong to the current user.');
        }

        if ($siteId !== null) {
            throw_unless($session->site_id === $siteId, AuthorizationException::class, 'AI creator session does not belong to the current site.');
        }
    }
}
