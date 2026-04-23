<?php

declare(strict_types=1);

namespace Capell\SeoTools\Support\Pipelines;

use Capell\SeoTools\DataObjects\AiCreatorData;
use Capell\SeoTools\Models\AiCreatorContext;
use Capell\SeoTools\Models\AiCreatorSession;
use Capell\SeoTools\Models\AIGenerationHistory;
use Capell\SeoTools\Support\AiRateLimiter;
use Capell\SeoTools\Support\AiResponse;
use Capell\SeoTools\Support\PrismProvider;
use Capell\SeoTools\Support\PromptRepository;
use Capell\SeoTools\Support\SectionRegistry;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;

class AiCreatorPipeline
{
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly PrismProvider $provider,
        private readonly AiRateLimiter $rateLimiter,
        private readonly SectionRegistry $sectionRegistry,
    ) {}

    /**
     * @return array<int, array<string, mixed>> The proposed sections array
     */
    public function execute(AiCreatorData $data): array
    {
        $payload = ['data' => $data, 'sections' => [], 'session' => null, 'context' => null, 'response' => null];

        $result = resolve(Pipeline::class)
            ->send($payload)
            ->through([
                fn (array $p, callable $next): array => $this->loadOrCreateSession($p, $next),
                fn (array $p, callable $next): array => $this->loadContext($p, $next),
                fn (array $p, callable $next): array => $this->checkRateLimit($p, $next),
                fn (array $p, callable $next): array => $this->executeAiCall($p, $next),
                fn (array $p, callable $next): array => $this->parseSections($p, $next),
                fn (array $p, callable $next): array => $this->persistResult($p, $next),
            ])
            ->thenReturn();

        return $result['sections'];
    }

    private function loadOrCreateSession(array $payload, callable $next): array
    {
        /** @var AiCreatorData $data */
        $data = $payload['data'];

        if ($data->existingSessionId !== null) {
            $session = AiCreatorSession::query()->findOrFail($data->existingSessionId);
        } else {
            $session = AiCreatorSession::query()->create([
                'site_id' => $data->siteId,
                'user_id' => $data->userId,
                'status' => 'generating',
                'stage' => 1,
                'intent' => $data->intent,
            ]);
        }

        $payload['session'] = $session;

        return $next($payload);
    }

    private function loadContext(array $payload, callable $next): array
    {
        /** @var AiCreatorData $data */
        $data = $payload['data'];

        $payload['context'] = AiCreatorContext::query()->where('site_id', $data->siteId)->first();

        return $next($payload);
    }

    private function checkRateLimit(array $payload, callable $next): array
    {
        $this->rateLimiter->checkLimit((string) $payload['data']->userId, 'ai_creator');

        return $next($payload);
    }

    private function executeAiCall(array $payload, callable $next): array
    {
        /** @var AiCreatorData $data */
        $data = $payload['data'];
        /** @var AiCreatorContext|null $context */
        $context = $payload['context'];

        $prompt = $this->prompts->get('ai_creator_layout');

        throw_unless($prompt, InvalidArgumentException::class, 'Missing ai_creator_layout prompt');

        $userMessage = strtr($prompt['user_template'], [
            '{{intent}}' => $data->intent,
            '{{tone}}' => $data->tone ?? $context?->tone ?? 'professional',
            '{{industry}}' => $data->industry ?? $context?->industry ?? 'general',
            '{{target_audience}}' => $data->targetAudience ?? $context?->target_audience ?? 'general audience',
            '{{section_types}}' => $this->sectionRegistry->forAi(),
            '{{brand_voice_notes}}' => $data->brandVoiceNotes ?? $context?->brand_voice_notes ?? 'none',
        ]);

        $response = $this->provider->chat([
            'model' => config('capell-seo-tools.features.ai_creator.model', 'gpt-4o'),
            'messages' => [
                ['role' => 'system', 'content' => $prompt['system']],
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        $payload['response'] = $response;

        return $next($payload);
    }

    private function parseSections(array $payload, callable $next): array
    {
        /** @var AiResponse $response */
        $response = $payload['response'];

        $content = trim($response->content);

        $content = (string) preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = (string) preg_replace('/\s*```$/', '', $content);

        $decoded = json_decode($content, true);

        throw_unless(
            is_array($decoded) && array_is_list($decoded),
            InvalidArgumentException::class,
            'AI response was not a valid JSON array of sections: ' . $content,
        );

        $payload['sections'] = $decoded;

        return $next($payload);
    }

    private function persistResult(array $payload, callable $next): array
    {
        /** @var AiCreatorSession $session */
        $session = $payload['session'];
        /** @var AiResponse $response */
        $response = $payload['response'];

        $history = AIGenerationHistory::query()->create([
            'action' => 'ai_creator_layout',
            'model' => $response->model,
            'input' => $payload['data']->intent,
            'output' => $response->content,
            'prompt_tokens' => $response->metadata['prompt_tokens'] ?? 0,
            'completion_tokens' => $response->metadata['completion_tokens'] ?? 0,
            'total_tokens' => $response->tokensUsed,
            'duration' => $response->duration,
        ]);

        $session->update([
            'status' => 'review',
            'stage' => 3,
            'layout_proposal' => $payload['sections'],
            'ai_history_id' => $history->id,
        ]);

        $payload['session'] = $session->fresh();

        return $next($payload);
    }
}
