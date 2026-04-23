<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Fixtures;

use Capell\SeoTools\Support\AiResponse;
use Capell\SeoTools\Support\PrismProvider;

class FakeOpenAIProviderForDescriptions extends PrismProvider
{
    public function chat(array $params): AiResponse
    {
        return new AiResponse(
            content: "- Description 1\n- Description 2",
            tokensUsed: 8,
            model: (string) ($params['model'] ?? 'test-model'),
            duration: 0.001,
            metadata: [
                'prompt_tokens' => 4,
                'completion_tokens' => 4,
                'finish_reason' => 'stop',
            ],
        );
    }
}
