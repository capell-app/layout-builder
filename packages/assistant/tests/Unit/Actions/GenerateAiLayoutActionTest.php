<?php

declare(strict_types=1);

use Capell\Assistant\Actions\GenerateAiLayoutAction;
use Capell\Assistant\DataObjects\AiCreatorData;
use Capell\Assistant\Support\Pipelines\AiCreatorPipeline;

it('returns sections array from pipeline', function (): void {
    $sections = [
        ['section_type' => 'hero-fullwidth', 'fields' => ['headline' => 'Welcome'], 'ai_metadata' => ['ai_placeholder' => true]],
    ];

    $pipeline = Mockery::mock(AiCreatorPipeline::class);
    $pipeline->shouldReceive('execute')->once()->andReturn($sections);

    $action = new GenerateAiLayoutAction($pipeline);

    $data = new AiCreatorData(
        siteId: 1,
        userId: 2,
        intent: 'Build a homepage',
    );

    $result = $action->handle($data);

    expect($result)->toBe($sections);
});
