<?php

declare(strict_types=1);

namespace Capell\Assistant\Support\Pipelines;

use Capell\Assistant\Contracts\AiActionContextInterface;
use Capell\Assistant\Models\AIGenerationHistory;
use Capell\Assistant\Support\AiRateLimiter;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\PrismProvider;
use Capell\Assistant\Support\PromptRepository;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;

class GenerateContentPipeline
{
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly PrismProvider $provider,
        private readonly AiRateLimiter $rateLimiter,
    ) {}

    /**
     * @param  array{context: AiActionContextInterface, options: array{user_id?: int|null, current_title?: string|null, target_length?: int|null, refactor?: bool|null}, action: object}  $input
     */
    public function execute(array $input): string
    {
        $payload = resolve(Pipeline::class)
            ->send($input)
            ->through([
                fn (array $payload, callable $next): array => $this->validateInput($payload, $next),
                fn (array $payload, callable $next): array => $this->checkRateLimit($payload, $next),
                fn (array $payload, callable $next): array => $this->executeAiCall($payload, $next),
                fn (array $payload, callable $next): array => $this->parseResponse($payload, $next),
                fn (array $payload, callable $next): array => $this->recordGeneration($payload),
            ])
            ->thenReturn();

        return (string) ($payload['result'] ?? '');
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
        $this->rateLimiter->checkLimit($identifier, 'content_generation');

        return $next($payload);
    }

    private function executeAiCall(array $payload, callable $next): array
    {
        /** @var AiActionContextInterface $context */
        $context = $payload['context'];
        $options = $payload['options'] ?? [];
        $prompt = $this->prompts->get('content_generation');

        $userMessage = strtr((string) ($prompt['user_template'] ?? ''), [
            '{{current_title}}' => (string) ($options['current_title'] ?? ''),
            '{{keywords}}' => (string) ($context->getKeywords() ?? ''),
            '{{content}}' => (string) ($context->getContent() ?? ''),
            '{{target_length}}' => ($options['target_length'] ?? null) !== null ? (string) $options['target_length'] : 'auto',
            '{{refactor}}' => ((bool) ($options['refactor'] ?? true)) ? 'yes' : 'no',
        ]);

        $messages = [
            ['role' => 'system', 'content' => (string) ($prompt['system'] ?? '')],
            ['role' => 'user', 'content' => $userMessage],
        ];

        $params = [
            'model' => (string) ($prompt['model'] ?? config('capell-assistant.prism.model')),
            'messages' => $messages,
            'max_tokens' => (int) config('capell-assistant.prism.max_tokens', 4096),
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
        $raw = trim($response->content);

        // Sanitize final HTML/text string
        $payload['result'] = $this->sanitizeHtml($raw);

        return $next($payload);
    }

    private function recordGeneration(array $payload): array
    {
        /** @var AiResponse $response */
        $response = $payload['ai_response'] ?? null;
        /** @var AiActionContextInterface $context */
        $context = $payload['context'];
        if ($response !== null) {
            AIGenerationHistory::query()->create([
                'action' => 'GeneratorPageContentAction',
                'model' => $response->model,
                'input' => $context->getContent(),
                'output' => (string) ($payload['result'] ?? ''),
                'prompt_tokens' => (int) ($response->metadata['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($response->metadata['completion_tokens'] ?? 0),
                'total_tokens' => (int) $response->tokensUsed,
                'duration' => (float) $response->duration,
                'page_id' => $context->getPageId(),
                'language_id' => $context->getLanguageId(),
                'metadata' => array_merge($response->metadata, [
                    'ai_messages' => $payload['ai_messages'] ?? null,
                    'ai_params' => $payload['ai_params'] ?? null,
                ]),
            ]);
        }

        return $payload;
    }

    /**
     * Basic sanitizer to keep AI output safe and user-friendly.
     * - strips script/style blocks
     * - removes inline event handlers (on*)
     * - neutralizes javascript: URLs
     * - removes external absolute links, preserves relative links
     */
    private function sanitizeHtml(string $html): string
    {
        // Remove <script> and <style> blocks
        $clean = preg_replace('#<\s*(script|style)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html) ?? $html;

        // Remove <iframe> blocks entirely
        $clean = preg_replace('#<\s*iframe[^>]*>.*?<\s*/\s*iframe\s*>#is', '', $clean) ?? $clean;

        // Remove inline event handlers like onclick, onerror, onload
        $clean = preg_replace('/\son\w+="[^"]*"/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\son\w+=\'[^\"]*\'/i', '', $clean) ?? $clean;
        $clean = preg_replace("/\\son\\w+='[^']*'/i", '', $clean) ?? $clean;

        // Neutralize javascript: URLs
        $clean = preg_replace('/\shref="javascript:[^"]*"/i', ' href="#"', $clean) ?? $clean;
        $clean = preg_replace('/\shref=\'javascript:[^\']*\'/i', " href='#'", $clean) ?? $clean;

        // Convert external absolute links to plain text or '#'
        // Preserve relative links (/, ./, ../) and anchors (#...)
        $clean = preg_replace_callback('#<a\s+[^>]*href\s*=\s*( ["\'])([^"\']+)\1[^>]*>(.*?)</a>#is', function (array $m): string {
            $href = $m[2];
            $text = trim(strip_tags($m[3]));

            $isRelative = str_starts_with($href, '/') || str_starts_with($href, './') || str_starts_with($href, '../') || str_starts_with($href, '#');
            $isAbsolute = preg_match('#^https?://#i', $href) === 1;

            if ($isRelative) {
                // keep relative links, but remove target and rel attributes
                $safeAnchor = preg_replace(['/\s+target=(["\']).*?\1/i', '/\s+rel=(["\']).*?\1/i'], ['', ''], $m[0]) ?? $m[0];

                return $safeAnchor;
            }

            if ($isAbsolute) {
                // Replace external links with a non-clickable span containing text
                return $text !== '' ? '<span>' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>' : '<span></span>';
            }

            // Unknown scheme: neutralize
            return '<span>' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
        }, $clean) ?? $clean;

        return $clean;
    }
}
