<?php

declare(strict_types=1);

namespace Capell\Assistant\Support\Pipelines;

use Capell\Assistant\Contracts\AiActionContextInterface;
use Capell\Assistant\Models\AIGenerationHistory;
use Capell\Assistant\Support\AiRateLimiter;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\AiResponseParser;
use Capell\Assistant\Support\OpenAIProvider;
use Capell\Assistant\Support\PromptRepository;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;

class SuggestMetaDescriptionsPipeline
{
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly OpenAIProvider $provider,
        private readonly AiResponseParser $parser,
        private readonly AiRateLimiter $rateLimiter,
    ) {}

    /**
     * @return array<int, string>
     */
    public function execute(array $input): array
    {
        $payload = resolve(Pipeline::class)
            ->send($input)
            ->through([
                fn (array $payload, callable $next): array => $this->validateInput($payload, $next),
                fn (array $payload, callable $next): array => $this->checkRateLimit($payload, $next),
                fn (array $payload, callable $next): array => $this->executeAiCall($payload, $next),
                fn (array $payload, callable $next): array => $this->parseResponse($payload, $next),
                fn (array $payload, callable $next): array => $this->recordGeneration($payload, $next),
            ])
            ->thenReturn();

        return $payload['result'];
    }

    private function validateInput(array $payload, callable $next): array
    {
        $context = $payload['context'] ?? null;
        throw_if(! $context instanceof AiActionContextInterface, InvalidArgumentException::class, 'Missing context');

        return $next($payload);
    }

    private function checkRateLimit(array $payload, callable $next): array
    {
        $identifier = (string) ($payload['options']['user_id'] ?? 'global');
        $this->rateLimiter->checkLimit($identifier, 'meta_suggestions');

        return $next($payload);
    }

    private function executeAiCall(array $payload, callable $next): array
    {
        /** @var AiActionContextInterface $context */
        $context = $payload['context'];
        $prompt = $this->prompts->get('meta_description');
        $content = $context->getContent();
        $keywords = $context->getKeywords();

        $userMessage = strtr((string) ($prompt['user_template'] ?? ''), [
            '{{content}}' => $content,
            '{{keywords}}' => $keywords,
        ]);

        $params = [
            'model' => (string) ($prompt['model'] ?? config('capell-assistant.prism.model')),
            'messages' => [
                ['role' => 'system', 'content' => (string) ($prompt['system'] ?? '')],
                ['role' => 'user', 'content' => $userMessage . "\nPlease provide 3 meta description options as a simple bullet list."],
            ],
            'max_tokens' => (int) config('capell-assistant.prism.max_tokens', 128),
            'temperature' => 0.7,
        ];

        $response = $this->provider->chat($params);
        $payload['ai_response'] = $response;

        return $next($payload);
    }

    private function parseResponse(array $payload, callable $next): array
    {
        /** @var AiResponse $response */
        $response = $payload['ai_response'];
        $parsed = $this->parser->parse($response->content);
        $payload['result'] = array_values(array_unique(array_map(static fn (array $row): string => (string) ($row['value'] ?? ''), $parsed)));

        return $next($payload);
    }

    private function recordGeneration(array $payload, callable $next): array
    {
        /** @var AiResponse $response */
        $response = $payload['ai_response'] ?? null;
        /** @var AiActionContextInterface $context */
        $context = $payload['context'];

        if ($response !== null) {
            AIGenerationHistory::query()->create([
                'action' => 'SuggestMetaDescriptionsAction',
                'model' => $response->model,
                'input' => $context->getContent(),
                'output' => implode("\n", (array) ($payload['result'] ?? [])),
                'prompt_tokens' => (int) ($response->metadata['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($response->metadata['completion_tokens'] ?? 0),
                'total_tokens' => (int) $response->tokensUsed,
                'duration' => (float) $response->duration,
                'metadata' => $response->metadata,
            ]);
        }

        return $next($payload);
    }
}
