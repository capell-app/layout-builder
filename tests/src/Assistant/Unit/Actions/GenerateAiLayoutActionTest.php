<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\GenerateAiLayoutAction;
use Capell\SeoTools\DataObjects\AiCreatorData;
use Capell\SeoTools\Support\Pipelines\AiCreatorPipeline;

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
