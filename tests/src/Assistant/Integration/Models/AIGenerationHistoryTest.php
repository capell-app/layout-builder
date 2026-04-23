<?php

declare(strict_types=1);

use Capell\SeoTools\Models\AIGenerationHistory;

uses()->group('admin-ai');

it('shows records in filament resource table', function (): void {
    AIGenerationHistory::query()->create([
        'action' => 'GeneratePageTitleAction',
        'model' => 'gpt-4-turbo',
        'input' => 'x',
        'output' => 'y',
        'prompt_tokens' => 1,
        'completion_tokens' => 2,
        'total_tokens' => 3,
        'duration' => 0.01,
        'metadata' => [],
    ]);

    $count = AIGenerationHistory::query()->count();
    expect($count)->toBeGreaterThan(0);
});
