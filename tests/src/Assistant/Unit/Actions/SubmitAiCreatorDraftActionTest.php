<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\SubmitAiCreatorDraftAction;
use Capell\SeoTools\Contracts\ContentTargetContract;
use Capell\SeoTools\Models\AiCreatorSession;
use Capell\SeoTools\Support\ContentTargetResolver;

it('applies sections via preferred target and marks session submitted', function (): void {
    $session = Mockery::mock(AiCreatorSession::class)->makePartial();
    $session->layout_proposal = [['section_type' => 'hero', 'fields' => ['headline' => 'Test']]];
    $session->shouldReceive('update')
        ->once()
        ->with(['status' => 'submitted']);

    $target = Mockery::mock(ContentTargetContract::class);
    $target->shouldReceive('apply')
        ->once()
        ->with($session->layout_proposal, $session);

    $resolver = Mockery::mock(ContentTargetResolver::class);
    $resolver->shouldReceive('preferred')->once()->andReturn($target);

    $action = new SubmitAiCreatorDraftAction($resolver);
    $action->handle($session);
});

it('skips applying when no target registered', function (): void {
    $session = Mockery::mock(AiCreatorSession::class)->makePartial();
    $session->layout_proposal = [];
    $session->shouldReceive('update')->once()->with(['status' => 'submitted']);

    $resolver = Mockery::mock(ContentTargetResolver::class);
    $resolver->shouldReceive('preferred')->once()->andReturn(null);

    $action = new SubmitAiCreatorDraftAction($resolver);
    $action->handle($session);
});
