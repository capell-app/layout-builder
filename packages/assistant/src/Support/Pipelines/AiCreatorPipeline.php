<?php

declare(strict_types=1);

namespace Capell\Assistant\Support\Pipelines;

use Capell\Assistant\DataObjects\AiCreatorData;
use Capell\Assistant\Models\AiCreatorContext;
use Capell\Assistant\Models\AiCreatorSession;
use Capell\Assistant\Models\AIGenerationHistory;
use Capell\Assistant\Support\AiRateLimiter;
use Capell\Assistant\Support\AiResponse;
use Capell\Assistant\Support\PrismProvider;
use Capell\Assistant\Support\PromptRepository;
use Capell\Assistant\Support\SectionRegistry;
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
            $session = AiCreatorSession::findOrFail($data->existingSessionId);
        } else {
            $session = AiCreatorSession::create([
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

        $payload['context'] = AiCreatorContext::where('site_id', $data->siteId)->first();

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
            'model' => config('capell-assistant.features.ai_creator.model', 'gpt-4o'),
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

        $history = AIGenerationHistory::create([
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
