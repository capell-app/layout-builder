<?php

declare(strict_types=1);

use Capell\SeoTools\Actions\GeneratorPageContentAction;
use Capell\SeoTools\Actions\RecordAiGenerationAction;
use Capell\SeoTools\Models\AIGenerationHistory;

uses()->group('admin-ai');

it('records generation with user_id in metadata', function (): void {
    $payload = [
        'action' => GeneratorPageContentAction::class,
        'model' => 'gpt-4-turbo',
        'input' => 'C',
        'output' => 'O',
        'prompt_tokens' => 10,
        'completion_tokens' => 20,
        'total_tokens' => 30,
        'duration' => 0.123,
        'metadata' => ['user_id' => 99],
        'pageable_id' => 123,
        'pageable_type' => 'page',
        'language_id' => 9,
    ];

    /** @var AIGenerationHistory $history */
    $history = RecordAiGenerationAction::run($payload);

    expect($history->metadata['user_id'] ?? null)->toBe(99);
    expect($history->action)->toBe(GeneratorPageContentAction::class);
});
