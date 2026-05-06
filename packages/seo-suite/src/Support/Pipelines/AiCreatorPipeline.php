<?php

declare(strict_types=1);

namespace Capell\SeoSuite\Support\Pipelines;

use Capell\SeoSuite\Actions\Ai\RecordAiGenerationAction;
use Capell\SeoSuite\Data\Ai\AiGenerationInputData;
use Capell\SeoSuite\Data\Ai\AiGenerationResultData;
use Capell\SeoSuite\DataObjects\AiCreatorData;
use Capell\SeoSuite\Models\AiCreatorContext;
use Capell\SeoSuite\Models\AiCreatorSession;
use Capell\SeoSuite\Support\AiRateLimiter;
use Capell\SeoSuite\Support\AiResponse;
use Capell\SeoSuite\Support\PrismProvider;
use Capell\SeoSuite\Support\PromptRepository;
use Capell\SeoSuite\Support\SectionRegistry;
use Illuminate\Pipeline\Pipeline;
use InvalidArgumentException;

class AiCreatorPipeline
{
    public function __construct(
        private readonly PromptRepository $prompts,
        private readonly PrismProvider $provider,
        private readonly AiRateLimiter $rateLimiter,
        private readonly SectionRegistry $sectionRegistry,
        private readonly RecordAiGenerationAction $recordAiGenerationAction,
    ) {}

    public function execute(AiGenerationInputData $input): AiGenerationResultData
    {
        $data = $input->creatorData;
        throw_unless($data instanceof AiCreatorData, InvalidArgumentException::class, 'Missing AI creator data');

        $payload = ['data' => $data, 'sections' => [], 'session' => null, 'context' => null, 'response' => null];
        $payload['input'] = $input;

        $result = resolve(Pipeline::class)
            ->send($payload)
            ->through([
                fn (array $pipelinePayload, callable $next): array => $this->loadOrCreateSession($pipelinePayload, $next),
                fn (array $pipelinePayload, callable $next): array => $this->loadContext($pipelinePayload, $next),
                fn (array $pipelinePayload, callable $next): array => $this->checkRateLimit($pipelinePayload, $next),
                fn (array $pipelinePayload, callable $next): array => $this->executeAiCall($pipelinePayload, $next),
                fn (array $pipelinePayload, callable $next): array => $this->parseSections($pipelinePayload, $next),
                fn (array $pipelinePayload, callable $next): array => $this->persistResult($pipelinePayload, $next),
            ])
            ->thenReturn();

        /** @var AiGenerationResultData $resultData */
        $resultData = $result['result_data'];

        return $resultData;
    }

    private function loadOrCreateSession(array $payload, callable $next): array
    {
        /** @var AiCreatorData $data */
        $data = $payload['data'];

        if ($data->existingSessionId !== null) {
            $session = AiCreatorSession::query()
                ->whereKey($data->existingSessionId)
                ->where('site_id', $data->siteId)
                ->where('user_id', $data->userId)
                ->where('status', 'review')
                ->firstOrFail();
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
            'model' => config('capell-seo-suite.features.ai_creator.model', 'gpt-4o'),
            'messages' => [
                ['role' => 'system', 'content' => $prompt['system']],
                ['role' => 'user', 'content' => $userMessage],
            ],
        ]);

        $payload['response'] = $response;
        $payload['ai_messages'] = [
            ['role' => 'system', 'content' => $prompt['system']],
            ['role' => 'user', 'content' => $userMessage],
        ];
        $payload['ai_params'] = [
            'model' => config('capell-seo-suite.features.ai_creator.model', 'gpt-4o'),
            'messages' => $payload['ai_messages'],
        ];

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

        throw_if(count($decoded) > 8, InvalidArgumentException::class, 'AI response may contain at most 8 sections.');

        $registeredSections = $this->sectionRegistry->all();
        $validatedSections = [];

        foreach ($decoded as $sectionIndex => $section) {
            throw_unless(
                is_array($section),
                InvalidArgumentException::class,
                'AI response section ' . ($sectionIndex + 1) . ' was not an object.',
            );

            $sectionType = $section['section_type'] ?? null;

            throw_unless(
                is_string($sectionType) && $sectionType !== '',
                InvalidArgumentException::class,
                'AI response section ' . ($sectionIndex + 1) . ' is missing a section_type.',
            );

            throw_unless(
                $registeredSections === [] || array_key_exists($sectionType, $registeredSections),
                InvalidArgumentException::class,
                'AI response section type "' . $sectionType . '" is not registered.',
            );

            $fields = $section['fields'] ?? [];

            throw_unless(
                is_array($fields),
                InvalidArgumentException::class,
                'AI response section ' . ($sectionIndex + 1) . ' fields must be an object.',
            );

            $validatedSections[] = [
                'section_type' => $sectionType,
                'fields' => $fields,
            ];
        }

        $payload['sections'] = $validatedSections;

        return $next($payload);
    }

    private function persistResult(array $payload, callable $next): array
    {
        /** @var AiGenerationInputData $input */
        $input = $payload['input'];
        /** @var AiCreatorSession $session */
        $session = $payload['session'];
        /** @var AiResponse $response */
        $response = $payload['response'];
        /** @var AiCreatorData $data */
        $data = $payload['data'];

        $resultData = AiGenerationResultData::make(
            actionKey: $input->actionKey,
            output: $payload['sections'],
            inputText: $data->intent,
            outputText: $response->content,
            response: $response,
            messages: $payload['ai_messages'] ?? null,
            params: $payload['ai_params'] ?? null,
            metadata: [
                'ai_creator_site_id' => $data->siteId,
                'ai_creator_user_id' => $data->userId,
            ],
            aiCreatorSessionId: $session->getKey(),
        );

        $history = $this->recordAiGenerationAction->handle($resultData);
        $resultData->history = $history;

        $session->update([
            'status' => 'review',
            'stage' => 3,
            'layout_proposal' => $payload['sections'],
            'ai_history_id' => $history->id,
        ]);

        $payload['session'] = $session->fresh();
        $payload['result_data'] = $resultData;

        return $next($payload);
    }
}
