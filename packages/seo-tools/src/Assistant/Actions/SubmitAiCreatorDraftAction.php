<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Actions;

use Capell\SeoTools\Assistant\Contracts\ContentTargetContract;
use Capell\SeoTools\Assistant\Models\AiCreatorSession;
use Capell\SeoTools\Assistant\Support\ContentTargetResolver;
use Lorisleiva\Actions\Concerns\AsAction;

class SubmitAiCreatorDraftAction
{
    use AsAction;

    public function __construct(
        private readonly ContentTargetResolver $targetResolver,
    ) {}

    public function handle(AiCreatorSession $session): void
    {
        $sections = (array) ($session->layout_proposal ?? []);

        $target = $this->targetResolver->preferred();

        if ($target instanceof ContentTargetContract) {
            $target->apply($sections, $session);
        }

        $session->update(['status' => 'submitted']);
    }
}
