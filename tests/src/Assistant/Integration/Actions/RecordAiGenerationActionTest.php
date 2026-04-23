<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\RecordAiGenerationAction;
use Capell\SeoTools\Events\AiGenerationCompleted;
use Capell\SeoTools\Models\AIGenerationHistory;
use Illuminate\Support\Facades\Event;

uses()->group('admin-ai');

it('records a generation and dispatches event', function (): void {
    Event::fake();
    $data = [
        'action' => 'TestAction',
        'model' => 'gpt-4-turbo',
        'input' => 'test',
        'output' => 'result',
        'prompt_tokens' => 1,
        'completion_tokens' => 2,
        'total_tokens' => 3,
        'duration' => 0.01,
        'metadata' => [],
    ];
    $history = RecordAiGenerationAction::run($data);
    expect($history)->toBeInstanceOf(AIGenerationHistory::class);
    Event::assertDispatched(AiGenerationCompleted::class);
});
