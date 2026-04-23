<?php

declare(strict_types=1);

namespace Capell\Tests\Assistant\Fixtures;

use Capell\SeoTools\Support\AiResponse;
use Capell\SeoTools\Support\PrismProvider;

class FakeOpenAIProviderForContent extends PrismProvider
{
    public function chat(array $params): AiResponse
    {
        return new AiResponse(
            content: "Hello\nWorld",
            tokensUsed: 10,
            model: (string) ($params['model'] ?? 'test-model'),
            duration: 0.001,
            metadata: [
                'prompt_tokens' => 5,
                'completion_tokens' => 5,
                'finish_reason' => 'stop',
            ],
        );
    }
}
