<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Fixtures;

use Capell\SeoTools\Support\AiResponse;
use Capell\SeoTools\Support\PrismProvider;

class FakeOpenAIProvider extends PrismProvider
{
    public function chat(array $params): AiResponse
    {
        return new AiResponse(
            content: "- Title A\n- Title B\n- Title C",
            tokensUsed: 12,
            model: (string) ($params['model'] ?? 'test-model'),
            duration: 0.001,
            metadata: [
                'prompt_tokens' => 6,
                'completion_tokens' => 6,
                'finish_reason' => 'stop',
            ],
        );
    }
}
