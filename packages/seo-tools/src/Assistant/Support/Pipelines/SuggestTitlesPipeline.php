<?php

declare(strict_types=1);

namespace Capell\SeoTools\Assistant\Support\Pipelines;

use Capell\SeoTools\Assistant\Contracts\AiActionContextInterface;
use Capell\SeoTools\Assistant\Models\AIGenerationHistory;
use Capell\SeoTools\Assistant\Support\AiRateLimiter;
use Capell\SeoTools\Assistant\Support\AiResponse;
use Capell\SeoTools\Assistant\Support\AiResponseParser;
use Capell\SeoTools\Assistant\Support\PrismProvider;
use Capell\SeoTools\Assistant\Support\PromptRepository;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;

class SuggestTitlesPipeline
{
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly PrismProvider $provider,
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
        throw_unless($context instanceof AiActionContextInterface, InvalidArgumentException::class, 'Missing AiActionContextInterface context');

        return $next($payload);
    }

    private function checkRateLimit(array $payload, callable $next): array
    {
        $identifier = (string) ($payload['options']['user_id'] ?? 'global');
        $this->rateLimiter->checkLimit($identifier, 'title_suggestions');

        return $next($payload);
    }

    private function executeAiCall(array $payload, callable $next): array
    {
        $context = $payload['context'];
        $options = $payload['options'] ?? [];
        $prompt = $this->prompts->get('title_generation');
        $content = $context->getContent();
        $keywords = $context->getKeywords();

        $userMessage = strtr((string) ($prompt['user_template'] ?? ''), [
            '{{content}}' => $content,
            '{{keywords}}' => $keywords,
            '{{current_title}}' => $options['current_title'] ?? '',
        ]);

        $messages = [
            ['role' => 'system', 'content' => (string) ($prompt['system'] ?? '')],
            ['role' => 'user', 'content' => $userMessage . "\nPlease provide 5 distinct title options as a simple bullet list."],
        ];

        $params = [
            'model' => (string) ($prompt['model'] ?? config('capell-assistant.prism.model')),
            'messages' => $messages,
            'max_tokens' => config('capell-assistant.prism.max_tokens', 128),
            'temperature' => 0.7,
        ];

        $response = $this->provider->chat($params);
        $payload['ai_response'] = $response;
        $payload['ai_messages'] = $messages;
        $payload['ai_params'] = $params;

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
                'action' => 'SuggestPageTitlesAction',
                'model' => $response->model,
                'input' => $context->getContent(),
                'output' => implode("\n", (array) ($payload['result'] ?? [])),
                'prompt_tokens' => (int) ($response->metadata['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($response->metadata['completion_tokens'] ?? 0),
                'total_tokens' => $response->tokensUsed,
                'duration' => $response->duration,
                'pageable_id' => $context->getPageId(),
                'pageable_type' => $context->getPageType(),
                'language_id' => $context->getLanguageId(),
                'metadata' => array_merge($response->metadata, [
                    'ai_messages' => $payload['ai_messages'] ?? null,
                    'ai_params' => $payload['ai_params'] ?? null,
                ]),
            ]);
        }

        return $next($payload);
    }
}
